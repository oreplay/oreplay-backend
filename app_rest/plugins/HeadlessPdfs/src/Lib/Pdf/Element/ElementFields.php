<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use App\Lib\Exception\InvalidPayloadException;
use HeadlessPdfs\Lib\Pdf\PageGeometry;

class ElementFields
{
    public const CENTER = 'center';
    public const MIN_SIZE = 1;
    public const MAX_SIZE = 300;
    private const DEFAULT_SIZE = 12.0;

    public static function string(array $el, string $key, string $path, int $maxLength): string
    {
        $value = $el[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidPayloadException($path . '.' . $key . ': is required and must be a string');
        }
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidPayloadException(
                $path . '.' . $key . ': exceeds the maximum length of ' . $maxLength . ' characters'
            );
        }
        return $value;
    }

    public static function size(array $el, string $path): float
    {
        if (!array_key_exists('size', $el)) {
            return self::DEFAULT_SIZE;
        }
        $size = $el['size'];
        if (!is_int($size) && !is_float($size)) {
            throw new InvalidPayloadException(self::sizeMessage($path));
        }
        if ($size < self::MIN_SIZE || $size > self::MAX_SIZE) {
            throw new InvalidPayloadException(self::sizeMessage($path));
        }
        return (float)$size;
    }

    public static function positionY(array $el, string $path): float|string
    {
        $y = $el['position']['y'] ?? null;
        if ($y === self::CENTER) {
            return self::CENTER;
        }
        if (!self::isNumberBetween($y, 0.0, PageGeometry::HEIGHT_MM, true)) {
            throw new InvalidPayloadException(
                $path . '.position.y: expected a number between 0 and '
                . (int)PageGeometry::HEIGHT_MM . ', or "' . self::CENTER . '" but got ' . json_encode($el)
            );
        }
        return (float)$y;
    }

    public static function positionX(array $el, string $path): float
    {
        $x = $el['position']['x'] ?? null;
        // the maximum is exclusive: a cell starting at the right edge would have zero width,
        // which tc-lib-pdf reinterprets as "extend to the right margin"
        if (!self::isNumberBetween($x, 0.0, PageGeometry::WIDTH_MM, false)) {
            throw new InvalidPayloadException(
                $path . '.position.x: expected a number between 0 and ' . (int)PageGeometry::WIDTH_MM
            );
        }
        return (float)$x;
    }

    private static function isNumberBetween($value, float $min, float $max, bool $inclusiveMax): bool
    {
        if (!is_int($value) && !is_float($value)) {
            return false;
        }
        return $value >= $min && ($inclusiveMax ? $value <= $max : $value < $max);
    }

    private static function sizeMessage(string $path): string
    {
        return $path . '.size: expected a number between ' . self::MIN_SIZE . ' and ' . self::MAX_SIZE;
    }
}
