<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\Lib\Pdf\PageGeometry;

class CenteredTextElement implements ElementRenderer
{
    public const MAX_CONTENT_LENGTH = 10000;

    public static function type(): string
    {
        return 'centeredElement';
    }

    public static function validate(array $el, string $path): array
    {
        return [
            'type' => self::type(),
            'content' => ElementFields::string($el, 'content', $path, self::MAX_CONTENT_LENGTH),
            'size' => ElementFields::size($el, $path),
            'color' => ElementFields::color($el, $path),
            'style' => ElementFields::style($el, $path),
            'y' => ElementFields::positionY($el, $path),
        ];
    }

    public function render(Tcpdf $pdf, array $el, PageGeometry $page): void
    {
        $isCentered = $el['y'] === ElementFields::CENTER;
        $pdf->addTextCellXY(
            $el['content'],
            -1,
            posx: 0.0,
            posy: $isCentered ? 0.0 : $el['y'],
            width: $page->widthMm,
            height: $isCentered ? $page->heightMm : 0.0,
            valign: $isCentered ? 'C' : 'T',
            halign: 'C',
            drawcell: false,
        );
    }
}
