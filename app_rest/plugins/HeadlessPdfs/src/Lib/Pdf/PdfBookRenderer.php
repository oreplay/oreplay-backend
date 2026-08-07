<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf;

use Cake\Http\Response;
use Com\Tecnick\Pdf\Page\Unit;
use Com\Tecnick\Pdf\PdfConformance;
use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\Lib\Exception\ImageFetchFailedException;
use HeadlessPdfs\Lib\Pdf\Element\BreakPageElement;
use HeadlessPdfs\Lib\Pdf\Element\ElementRegistry;
use HeadlessPdfs\Lib\Pdf\Element\ElementRenderer;
use RestApi\Lib\RestRenderer;

/**
 * Turns a normalized pdfBook into PDF bytes.
 *
 * Assigning an instance to a controller's $this->return is enough to stream it as a
 * download: RestApiController::beforeRender() handles RestRenderer instances itself.
 */
class PdfBookRenderer implements RestRenderer
{
    private const FONT_FAMILY = 'notosans';
    private const MAX_REMOTE_IMAGE_BYTES = 2097152;

    private ?int $backgroundImageId = null;

    /** @var array<string, ElementRenderer> */
    private array $renderers = [];

    /**
     * @param array $pdfBook Normalized output of PdfBookValidator::validate().
     */
    public function __construct(protected readonly array $pdfBook)
    {
    }

    public function setHeadersForDownload(Response $response, $title = null): Response
    {
        // $title is never passed by beforeRender(); the filename is already validated
        return $response
            ->withType('pdf')
            ->withHeader('Content-Disposition', $this->contentDisposition($this->pdfBook['filename']));
    }

    private function contentDisposition(string $filename): string
    {
        $asciiFallback = preg_replace('/[^\x20-\x7E]/', '_', $filename);
        return 'attachment; filename="' . $asciiFallback . '"'
            . "; filename*=UTF-8''" . rawurlencode($filename);
    }

    public function render(): string
    {
        $pdf = $this->newDocument();
        $this->loadBackgroundImage($pdf);
        $this->newPage($pdf);

        foreach ($this->pdfBook['elements'] as $element) {
            if ($element['type'] === BreakPageElement::type()) {
                $this->newPage($pdf);
                continue;
            }
            if (isset($element['size'])) {
                $this->useFontSize($pdf, $element['size']);
            }
            $this->rendererFor($element['type'])->render($pdf, $element);
        }

        return $pdf->getOutPDFString();
    }

    protected function rendererFor(string $type): ElementRenderer
    {
        $class = ElementRegistry::all()[$type];
        return $this->renderers[$type] ??= new $class();
    }

    private function newDocument(): Tcpdf
    {
        $pdf = new Tcpdf(
            unit: Unit::Millimeter,
            isunicode: true,
            subsetfont: true,
            compress: true,
            mode: PdfConformance::None,
            objEncrypt: null,
            fileOptions: [
                'allowedHosts' => PdfBookValidator::allowedImageHosts(),
                'allowedPaths' => [K_PATH_FONTS],
                'maxRemoteSize' => self::MAX_REMOTE_IMAGE_BYTES,
                'curlopts' => [CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8],
                // no 'fixedCurlOpts' override: the library's own fixed options already pin
                // SSL_VERIFYHOST/SSL_VERIFYPEER and, critically, RETURNTRANSFER and
                // FAILONERROR — overriding them here would drop those two.
            ],
        );
        $pdf->setCreator('OReplay');
        $pdf->setPDFFilename($this->pdfBook['filename']);
        return $pdf;
    }

    protected function loadBackgroundImage(Tcpdf $pdf): void
    {
        if ($this->pdfBook['img'] === null) {
            return;
        }
        try {
            $this->backgroundImageId = $pdf->image->add($this->pdfBook['img']);
        } catch (\Throwable $e) {
            throw new ImageFetchFailedException(
                'Could not fetch the background image: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    private function newPage(Tcpdf $pdf): void
    {
        $page = $pdf->addPage();
        $background = $this->backgroundContent($pdf, $page);
        if ($background !== '') {
            $pdf->page->addContent($background);
        }
    }

    protected function backgroundContent(Tcpdf $pdf, array $page): string
    {
        if ($this->backgroundImageId === null) {
            return '';
        }
        return $pdf->image->getSetImage(
            $this->backgroundImageId,
            0,
            0,
            PageGeometry::WIDTH_MM,
            PageGeometry::HEIGHT_MM,
            $page['height'],
        );
    }

    /**
     * insert() must run on every call, not just on a size change: besides emitting the
     * font operator, it sets the font stack's current font, which addTextCellXY() reads
     * for its width/height metrics. Caching by size would leave stale metrics in place
     * whenever a later element reuses an earlier size.
     */
    private function useFontSize(Tcpdf $pdf, float $size): void
    {
        $font = $pdf->font->insert($pdf->pon, self::FONT_FAMILY, '', $size);
        $pdf->page->addContent($font['out']);
    }
}
