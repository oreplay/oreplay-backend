<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\Lib\Pdf\PageGeometry;

class TextElement implements ElementRenderer
{
    public const MAX_CONTENT_LENGTH = 10000;

    public static function type(): string
    {
        return 'text';
    }

    public static function validate(array $el, string $path): array
    {
        return [
            'type' => self::type(),
            'content' => ElementFields::string($el, 'content', $path, self::MAX_CONTENT_LENGTH),
            'size' => ElementFields::size($el, $path),
            'color' => ElementFields::color($el, $path),
            'style' => ElementFields::style($el, $path),
            'x' => ElementFields::positionX($el, $path),
            'y' => ElementFields::positionY($el, $path),
        ];
    }

    public function render(Tcpdf $pdf, array $el, PageGeometry $page): void
    {
        $availableWidth = $page->widthMm - $el['x'];
        if ($availableWidth <= 0.0) {
            return;
        }
        $isCentered = $el['y'] === ElementFields::CENTER;
        $pdf->addTextCellXY(
            $el['content'],
            -1,
            posx: $el['x'],
            posy: $isCentered ? 0.0 : $el['y'],
            width: $availableWidth,
            height: $isCentered ? $page->heightMm : 0.0,
            valign: $isCentered ? 'C' : 'T',
            halign: 'L',
            drawcell: false,
        );
    }
}
