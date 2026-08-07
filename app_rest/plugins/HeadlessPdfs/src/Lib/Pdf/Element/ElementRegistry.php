<?php

declare(strict_types = 1);

namespace HeadlessPdfs\Lib\Pdf\Element;

use App\Lib\Exception\InvalidPayloadException;

class ElementRegistry
{
    /**
     * @return array<string, class-string<ElementRenderer>>
     */
    public static function all(): array
    {
        return [
            CenteredTextElement::type() => CenteredTextElement::class,
            TextElement::type() => TextElement::class,
            BreakPageElement::type() => BreakPageElement::class,
        ];
    }

    /**
     * @return class-string<ElementRenderer>
     */
    public static function classFor($type, string $path): string
    {
        $all = self::all();
        if (!is_string($type) || !isset($all[$type])) {
            throw new InvalidPayloadException(
                $path . '.type: unknown element type ' . json_encode($type)
            );
        }
        return $all[$type];
    }
}
