<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use Com\Tecnick\Pdf\Tcpdf;
use HeadlessPdfs\Lib\Pdf\PageGeometry;

class BreakPageElement implements ElementRenderer
{
    public static function type(): string
    {
        return 'breakPage';
    }

    public static function validate(array $el, string $path): array
    {
        return ['type' => self::type()];
    }

    public function render(Tcpdf $pdf, array $el, PageGeometry $page): void
    {
    }
}
