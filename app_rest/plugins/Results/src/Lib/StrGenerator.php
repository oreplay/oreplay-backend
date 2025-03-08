<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Http\Exception\InternalErrorException;

class StrGenerator
{
    public const LENGTH = 6;

    public static function hex($length = self::LENGTH): string
    {
        if (function_exists('random_bytes')) {
            $randomData = random_bytes($length);
            if ($randomData !== false && strlen($randomData) === $length) {
                return substr(str_shuffle(bin2hex($randomData)), 0, $length);
            }
        }
        throw new InternalErrorException('Cannot generate token');
    }

    public static function generate(int $length = self::LENGTH): string
    {
        $CHARACTERS = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $charactersLength = strlen($CHARACTERS);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $CHARACTERS[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

}
