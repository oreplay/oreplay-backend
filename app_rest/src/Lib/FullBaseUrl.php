<?php

namespace App\Lib;

use Cake\Http\Exception\NotImplementedException;

class FullBaseUrl
{
    public static function host(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            if (strpos($_SERVER['HTTP_HOST'], 'dev') === 0) {
                $scheme = 'http://';
            } else {
                $scheme = 'https://';
            }
            return $scheme . $_SERVER['HTTP_HOST'];
        }
        throw new NotImplementedException('Not implemented for commands');
    }
}
