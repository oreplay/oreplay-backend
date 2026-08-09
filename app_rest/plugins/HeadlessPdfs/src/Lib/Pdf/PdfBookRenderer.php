<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf;

use Cake\Http\Response;
use Com\Tecnick\File\File;
use Com\Tecnick\Pdf\Import\PageTemplateInterface;
use Com\Tecnick\Pdf\Page\Unit;
use Com\Tecnick\Pdf\PdfConformance;
use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\HeadlessPdfsPlugin;
use HeadlessPdfs\Lib\Exception\ImageFetchFailedException;
use HeadlessPdfs\Lib\Pdf\Element\BreakPageElement;
use HeadlessPdfs\Lib\Pdf\Element\ElementRegistry;
use HeadlessPdfs\Lib\Pdf\Element\ElementRenderer;
use RestApi\Lib\RestRenderer;

class PdfBookRenderer implements RestRenderer
{
    private const FONT_FAMILY = 'notosans';
    private const MAX_REMOTE_BACKGROUND_BYTES = 8388608;
    private const DOWNLOAD_FILENAME_HEADER = 'Content-Disposition';
    private const CONNECT_TIMEOUT_SECONDS = 4;
    private const TOTAL_TIMEOUT_SECONDS = 8;
    private const BACKGROUND_SOURCE_PAGE = 1;

    private ?int $backgroundImageId = null;
    private ?PageTemplateInterface $backgroundPdfPage = null;
    private PageGeometry $pageGeometry;

    /** @var array<string, ElementRenderer> */
    private array $renderers = [];

    /**
     * @param array $pdfBook Normalized output of PdfBookValidator::validate().
     */
    public function __construct(protected readonly array $pdfBook)
    {
        $this->pageGeometry = PageGeometry::a4();
    }

    public function setHeadersForDownload(Response $response, $title = null): Response
    {
        // $title is never passed by beforeRender(); the filename is already validated
        return $response
            ->withType('pdf')
            ->withHeader(
                self::DOWNLOAD_FILENAME_HEADER,
                $this->contentDisposition($this->pdfBook['filename']),
            )
            ->withHeader('Access-Control-Expose-Headers', self::DOWNLOAD_FILENAME_HEADER);
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
        $this->loadBackgroundPdfPage($pdf);
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
            if (isset($element['color'])) {
                $this->useFillColor($pdf, $element['color']);
            }
            $this->rendererFor($element['type'])->render($pdf, $element, $this->pageGeometry);
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
        self::ensureFontPathIsDefinedForTcLibPdf();
        self::ensureRemoteFilesAreFetchedWithCurl();
        $pdf = new Tcpdf(
            unit: Unit::Millimeter,
            isunicode: true,
            subsetfont: true,
            compress: true,
            mode: PdfConformance::None,
            objEncrypt: null,
            fileOptions: [
                'allowedHosts' => PdfBookValidator::allowedBackgroundHosts(),
                'allowedPaths' => [K_PATH_FONTS],
                'maxRemoteSize' => self::MAX_REMOTE_BACKGROUND_BYTES,
                'curlopts' => self::curlOptions(),
                // no 'fixedCurlOpts' override: the library's own fixed options already pin
                // SSL_VERIFYHOST/SSL_VERIFYPEER and, critically, RETURNTRANSFER and
                // FAILONERROR — overriding them here would drop those two.
            ],
        );
        $pdf->setCreator('OReplay');
        $pdf->setPDFFilename($this->pdfBook['filename']);
        return $pdf;
    }

    private static function ensureFontPathIsDefinedForTcLibPdf(): void
    {
        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', HeadlessPdfsPlugin::fontsPath());
        }
    }

    private static function ensureRemoteFilesAreFetchedWithCurl(): void
    {
        if (!defined('FORCE_CURL')) {
            define('FORCE_CURL', true);
        }
    }

    protected function loadBackgroundPdfPage(Tcpdf $pdf): void
    {
        $background = $this->pdfBook['backgroundPdf'] ?? null;
        if ($background === null) {
            return;
        }
        try {
            $sourceId = $pdf->setImportSourceData($this->fetchBackgroundPdf($background));
            $this->backgroundPdfPage = $pdf->importPage($sourceId, self::BACKGROUND_SOURCE_PAGE);
        } catch (\Throwable $e) {
            throw new ImageFetchFailedException(
                'Could not use the background PDF: ' . $e->getMessage(),
                null,
                $e,
            );
        }
        $this->pageGeometry = new PageGeometry(
            $pdf->toUnit($this->backgroundPdfPage->getWidth()),
            $pdf->toUnit($this->backgroundPdfPage->getHeight()),
        );
    }

    private function fetchBackgroundPdf(string $url): string
    {
        $file = new File(
            PdfBookValidator::allowedBackgroundHosts(),
            self::MAX_REMOTE_BACKGROUND_BYTES,
            self::curlOptions(),
        );
        $data = $file->getFileData($url);
        if ($data === false || $data === '') {
            throw new ImageFetchFailedException('Could not download the background PDF: ' . $url);
        }
        return $data;
    }

    private static function curlOptions(): array
    {
        return [
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TOTAL_TIMEOUT_SECONDS,
        ];
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
        $page = $pdf->addPage([
            'format' => '',
            'width' => $this->pageGeometry->widthMm,
            'height' => $this->pageGeometry->heightMm,
            'orientation' => $this->pageGeometry->widthMm > $this->pageGeometry->heightMm ? 'L' : 'P',
        ]);
        $this->stampBackgroundPdfPage($pdf);
        $background = $this->backgroundContent($pdf, $page);
        if ($background !== '') {
            $pdf->page->addContent($background);
        }
    }

    protected function stampBackgroundPdfPage(Tcpdf $pdf): void
    {
        if ($this->backgroundPdfPage === null) {
            return;
        }
        $pdf->useImportedPage(
            $this->backgroundPdfPage,
            0.0,
            0.0,
            $this->pageGeometry->widthMm,
            $this->pageGeometry->heightMm,
            ['keepAspectRatio' => false],
        );
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
            $this->pageGeometry->widthMm,
            $this->pageGeometry->heightMm,
            $page['height'],
        );
    }

    /**
     * Emitted for every element rather than only on a change, because each element carries
     * its own colour: skipping it would leak the previous element's colour into a default one.
     */
    private function useFillColor(Tcpdf $pdf, string $color): void
    {
        $pdf->page->addContent($pdf->color->getPdfFillColor($color));
    }

    private function useFontSize(Tcpdf $pdf, float $size): void
    {
        $font = $pdf->font->insert($pdf->pon, self::FONT_FAMILY, '', $size);
        $pdf->page->addContent($font['out']);
    }
}
