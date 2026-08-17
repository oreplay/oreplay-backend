<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

/**
 * Reads a node of the array Cake's Xml::toArray() produces, whose shape depends on the document rather
 * than on the schema: a repeated element is one object when it occurs once and a list when it occurs
 * twice, and an element without attributes is a plain string instead of an array.
 *
 * Splits_CEEBO.xml has a class with one PersonResult whose Result has one SplitTime, which is every one
 * of those cases at once.
 */
class IofNode
{
    public static function listOf(mixed $node): array
    {
        if ($node === null || $node === '' || $node === []) {
            return [];
        }
        if (!is_array($node)) {
            return [$node];
        }
        return array_is_list($node) ? $node : [$node];
    }

    public static function firstOf(mixed $node): array
    {
        $first = self::listOf($node)[0] ?? [];
        return is_array($first) ? $first : [];
    }

    /**
     * An element carrying attributes keeps its text under '@'; a bare one is the string itself.
     */
    public static function textOf(mixed $node): string
    {
        if (is_array($node)) {
            return (string)($node['@'] ?? '');
        }
        return (string)$node;
    }

    public static function repeatedTextOf(mixed $node, int $index): string
    {
        return self::textOf(self::listOf($node)[$index] ?? null);
    }
}
