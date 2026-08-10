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
    public const DEFAULT_COLOR = '#000000';
    public const DEFAULT_STYLE = 'regular';
    private const DEFAULT_SIZE = 12.0;
    private const HEX_COLOR = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    /**
     * Only the styles with a font file shipped in resources/fonts. Italic is left out on
     * purpose: tc-lib-pdf-font would synthesise it by slanting the regular face, and its
     * bold-italic derives from the regular face rather than the bold one.
     */
    private const FONT_STYLE_CODES = [
        'regular' => '',
        'bold' => 'B',
    ];

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

    /**
     * Hex only: a named colour would resolve through the spot-colour table, which needs a
     * colour space registered in the page resources that nothing here writes.
     */
    public static function color(array $el, string $path): string
    {
        $color = $el['color'] ?? self::DEFAULT_COLOR;
        if (!is_string($color) || preg_match(self::HEX_COLOR, $color) !== 1) {
            throw new InvalidPayloadException(
                $path . '.color: expected a hex colour such as "#1a2b3c" but got '
                . json_encode($color)
            );
        }
        return $color;
    }

    public static function style(array $el, string $path): string
    {
        $style = $el['style'] ?? self::DEFAULT_STYLE;
        if (!is_string($style) || !array_key_exists($style, self::FONT_STYLE_CODES)) {
            throw new InvalidPayloadException(
                $path . '.style: expected one of "'
                . implode('", "', array_keys(self::FONT_STYLE_CODES))
                . '" but got ' . json_encode($style)
            );
        }
        return $style;
    }

    public static function fontStyleCode(string $style): string
    {
        return self::FONT_STYLE_CODES[$style] ?? '';
    }

    public static function positionY(array $el, string $path): float|string
    {
        $y = $el['position']['y'] ?? null;
        if ($y === self::CENTER) {
            return self::CENTER;
        }
        if (!self::isNumberBetween($y, 0.0, PageGeometry::MAX_SIDE_MM, true)) {
            throw new InvalidPayloadException(
                $path . '.position.y: expected a number between 0 and '
                . (int)PageGeometry::MAX_SIDE_MM . ', or "' . self::CENTER . '" but got ' . json_encode($el)
            );
        }
        return (float)$y;
    }

    public static function positionX(array $el, string $path): float
    {
        $x = $el['position']['x'] ?? null;
        if (!self::isNumberBetween($x, 0.0, PageGeometry::MAX_SIDE_MM, false)) {
            throw new InvalidPayloadException(
                $path . '.position.x: expected a number between 0 and ' . (int)PageGeometry::MAX_SIDE_MM
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
