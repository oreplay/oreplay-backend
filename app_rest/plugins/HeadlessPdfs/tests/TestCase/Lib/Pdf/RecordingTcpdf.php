<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Test\TestCase\Lib\Pdf;

use Com\Tecnick\Pdf\Page\Unit;
use Com\Tecnick\Pdf\PdfConformance;
use Com\Tecnick\Pdf\Tcpdf;
use Com\Tecnick\Pdf\TextFitMode;
use Com\Tecnick\Pdf\TextHAlign;
use Com\Tecnick\Pdf\TextVAlign;
use Com\Tecnick\Unicode\TextDirection;

class RecordingTcpdf extends Tcpdf
{
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    public function __construct()
    {
        parent::__construct(
            unit: Unit::Millimeter,
            isunicode: true,
            subsetfont: true,
            compress: true,
            mode: PdfConformance::None,
            objEncrypt: null,
        );
    }

    public function addPage(array $data = []): array
    {
        $page = parent::addPage($data);
        $this->calls[] = ['method' => 'addPage', 'args' => []];
        return $page;
    }

    public function addTextCellXY(
        string $txt,
        int $pid = -1,
        float $posx = 0,
        float $posy = 0,
        float $width = 0,
        float $height = 0,
        float $offset = 0,
        float $linespace = 0,
        string|TextVAlign $valign = 'T',
        string|TextHAlign $halign = '',
        ?array $cell = null,
        array $styles = [],
        float $strokewidth = 0,
        float $wordspacing = 0,
        float $leading = 0,
        float $rise = 0,
        bool $jlast = true,
        bool $fill = true,
        bool $stroke = false,
        bool $underline = false,
        bool $linethrough = false,
        bool $overline = false,
        bool $clip = false,
        bool $drawcell = true,
        string|TextDirection $forcedir = '',
        ?array $shadow = null,
        string|TextFitMode $fit = '',
    ): void {
        $this->calls[] = ['method' => 'addTextCellXY', 'args' => [
            'txt' => $txt,
            'posx' => $posx,
            'posy' => $posy,
            'width' => $width,
            'height' => $height,
            'valign' => $valign,
            'halign' => $halign,
            'drawcell' => $drawcell,
        ]];
    }

    /** @return array<int, array> all recorded calls to the named method */
    public function callsTo(string $method): array
    {
        return array_values(array_map(
            fn (array $call) => $call['args'],
            array_filter($this->calls, fn (array $call) => $call['method'] === $method),
        ));
    }
}
