<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

/**
 * Writes numbers the way the Java desktop client writes them, because the two formats have to agree.
 * UploadHelper::md5Encode() compares scalars as strings, so "700" and "700.0" hash differently and the
 * same class would look changed depending on which format it arrived in.
 */
class ClientNumberFormat
{
    /**
     * A course length or climb, stringified from a Java Double: 700 becomes "700.0".
     */
    public static function distance(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $number = (float)$value;
        return self::isWhole($number) ? sprintf('%.1f', $number) : (string)$number;
    }

    /**
     * A time or a score, which stays an integer when it has no fractional part so 142 does not become
     * 142.0.
     */
    public static function number(mixed $value): int|float
    {
        $number = (float)$value;
        return self::isWhole($number) ? (int)$number : $number;
    }

    private static function isWhole(float $number): bool
    {
        return $number === floor($number);
    }
}
