<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf;

use Cake\Http\Response;
use Cake\TestSuite\TestCase;
use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\Lib\Exception\ImageFetchFailedException;
use HeadlessPdfs\Lib\Pdf\PdfBookRenderer;
use HeadlessPdfs\Lib\Pdf\PdfBookValidator;

class PdfBookRendererTest extends TestCase
{
    private function render(array $book): string
    {
        return (new PdfBookRenderer(PdfBookValidator::validate($book)))->render();
    }

    private function pageCount(string $pdfBytes): int
    {
        $parsed = (new \Com\Tecnick\Pdf\Parser\Parser())->parse($pdfBytes);
        return $this->countPageObjects($parsed);
    }

    /**
     * Recursively counts ['/', 'Page'] name tokens anywhere in the parsed tree.
     *
     * Searching rather than walking a known path keeps this independent of the parser's
     * result shape. The page tree root is ['/', 'Pages'], so it is not miscounted.
     */
    private function countPageObjects($node): int
    {
        if (!is_array($node)) {
            return 0;
        }
        $count = (($node[0] ?? null) === '/' && ($node[1] ?? null) === 'Page') ? 1 : 0;
        foreach ($node as $child) {
            $count += $this->countPageObjects($child);
        }
        return $count;
    }

    /**
     * @return array<int, string> the "x y" operand pair of each "x y Td" operator found in
     *         the page's text content stream, in document order (one per rendered element)
     */
    private function textPositions(string $pdfBytes): array
    {
        $parsed = (new \Com\Tecnick\Pdf\Parser\Parser())->parse($pdfBytes);
        $content = $this->findTextContentStream($parsed) ?? '';
        preg_match_all('/([\d.]+) ([\d.]+) Td/', $content, $matches);
        return array_map(fn ($x, $y) => $x . ' ' . $y, $matches[1], $matches[2]);
    }

    /**
     * Finds the first decoded stream that contains a text object ('BT'), i.e. the page's
     * content stream, distinguishing it from binary streams such as embedded font files.
     */
    private function findTextContentStream($node): ?string
    {
        if (!is_array($node)) {
            return null;
        }
        if (
            ($node[0] ?? null) === 'stream'
            && is_string($node[3][0] ?? null)
            && str_contains($node[3][0], 'BT')
        ) {
            return $node[3][0];
        }
        foreach ($node as $child) {
            $found = $this->findTextContentStream($child);
            if ($found !== null) {
                return $found;
            }
        }
        return null;
    }

    public function testRender_producesPdfBytes()
    {
        $bytes = $this->render([
            'sections' => [['elements' => [
                ['type' => 'centeredElement', 'content' => 'Ada Øst Wąs', 'size' => 36,
                 'position' => ['y' => 'center']],
            ]]],
        ]);

        $this->assertStringStartsWith('%PDF-', $bytes);
    }

    public function testRender_theExampleBookIsTwoPages()
    {
        $bytes = $this->render([
            'layout' => 'default',
            'sections' => [
                ['elements' => [
                    ['type' => 'centeredElement', 'content' => 'Título del documento', 'size' => 36,
                     'position' => ['y' => 'center']],
                    ['type' => 'breakPage'],
                    ['type' => 'centeredElement', 'content' => 'Segunda página', 'size' => 24,
                     'position' => ['y' => 150]],
                ]],
                ['elements' => [
                    ['type' => 'text', 'content' => 'Otra sección', 'size' => 18,
                     'position' => ['x' => 100, 'y' => 250]],
                ]],
            ],
        ]);

        $this->assertEquals(2, $this->pageCount($bytes), 'sections do not start new pages');
    }

    public function testRender_eachBreakPageAddsOnePage()
    {
        $bytes = $this->render([
            'sections' => [['elements' => [
                ['type' => 'breakPage'],
                ['type' => 'breakPage'],
            ]]],
        ]);

        $this->assertEquals(3, $this->pageCount($bytes), 'implicit first page plus two breaks');
    }

    public function testRender_fontSizeChange_doesNotPolluteSubsequentCenteringMetrics()
    {
        // font->insert() has a side effect beyond emitting the Tf operator: it becomes the
        // font stack's *current* font, which addTextCellXY() reads to compute centering.
        // Caching by size and skipping insert() on a repeat therefore leaves centering
        // computed against whichever size ran most recently, even though the Tf operator
        // written into the content stream is still correct.
        $bytes = $this->render([
            'sections' => [['elements' => [
                ['type' => 'centeredElement', 'content' => 'SAME', 'size' => 36, 'position' => ['y' => 100]],
                ['type' => 'centeredElement', 'content' => 'X', 'size' => 8, 'position' => ['y' => 200]],
                ['type' => 'centeredElement', 'content' => 'SAME', 'size' => 36, 'position' => ['y' => 100]],
            ]]],
        ]);

        $positions = $this->textPositions($bytes);
        $this->assertCount(3, $positions, 'expected one Td position per centered element');
        $this->assertSame(
            $positions[0],
            $positions[2],
            'identical content and size at index 0 and 2 must centre to the same position; a '
                . "stale font-size cache would centre index 2 using the size-8 element's metrics",
        );
    }

    public function testSetHeadersForDownload_setsContentTypeAndFilename()
    {
        $renderer = new PdfBookRenderer(
            PdfBookValidator::validate([
                'filename' => 'certificates',
                'sections' => [['elements' => [['type' => 'breakPage']]]],
            ])
        );

        $response = $renderer->setHeadersForDownload(new Response());

        $this->assertEquals('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(
            'attachment; filename="certificates.pdf"',
            $response->getHeaderLine('Content-Disposition'),
        );
    }

    public function testSetHeadersForDownload_worksBeforeRenderIsCalled()
    {
        // beforeRender() calls setHeadersForDownload() first, so this must not depend on render()
        $renderer = new PdfBookRenderer(
            PdfBookValidator::validate($this->minimalBook())
        );
        $response = $renderer->setHeadersForDownload(new Response());
        $this->assertEquals('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $renderer->render());
    }

    public function testRender_withoutBackground_drawsNothing()
    {
        $renderer = new BackgroundCountingRenderer(PdfBookValidator::validate($this->minimalBook()));
        $renderer->render();

        $this->assertEquals(0, $renderer->backgroundDrawn);
    }

    public function testRender_unreachableAllowedHost_is502()
    {
        // port 1 on loopback refuses immediately: deterministic, and no external network
        putenv('PDF_IMAGE_ALLOWED_HOSTS=127.0.0.1');
        try {
            $renderer = new PdfBookRenderer(PdfBookValidator::validate([
                'img' => 'http://127.0.0.1:1/bg.png',
                'sections' => [['elements' => [['type' => 'breakPage']]]],
            ]));

            $this->expectException(ImageFetchFailedException::class);
            $this->expectExceptionCode(502);
            $renderer->render();
        } finally {
            putenv('PDF_IMAGE_ALLOWED_HOSTS');
        }
    }

    private function waitUntilListening($process, int $port): bool
    {
        $deadline = microtime(true) + 3.0;
        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                return false;
            }
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($conn !== false) {
                fclose($conn);
                return true;
            }
            usleep(20000);
        }
        return false;
    }

    private function minimalBook(): array
    {
        return ['sections' => [['elements' => [
            ['type' => 'centeredElement', 'content' => 'x', 'position' => ['y' => 10]],
        ]]]];
    }
}

class BackgroundCountingRenderer extends PdfBookRenderer
{
    public int $backgroundDrawn = 0;
    private bool $hasBackground = false;

    protected function loadBackgroundImage(Tcpdf $pdf): void
    {
        $this->hasBackground = $this->pdfBook['img'] !== null;
    }

    protected function backgroundContent(Tcpdf $pdf, array $page): string
    {
        if ($this->hasBackground) {
            $this->backgroundDrawn++;
        }
        return '';
    }
}
