<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf;

final class PageGeometry
{
    public const WIDTH_MM = 210.0;
    public const HEIGHT_MM = 297.0;
    public const MAX_SIDE_MM = 1189.0;

    public function __construct(
        public readonly float $widthMm,
        public readonly float $heightMm,
    ) {
    }

    public static function a4(): self
    {
        return new self(self::WIDTH_MM, self::HEIGHT_MM);
    }
}
