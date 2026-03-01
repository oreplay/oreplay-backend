<?php

declare(strict_types = 1);

namespace App\Lib\ProxyFront;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\Http\Client;
use RestApi\Lib\Exception\DetailedException;

class FrontUtil
{
    public static function getOgImage(string $text): string
    {
        $base = 'https://or-img.gumlet.io/oreplay-og.png';
        $params = [
            'sharp' => 'false',
            'text' => $text,
            'txt-size' => '42',
            'text_color' => '#5e5c64',
            'text_bg_color' => '#ffffff',
            'text_left' => '110',
            'text_top' => '260',
            'text_align' => 'left',
            'text_line_height' => '15',
            //'w' => '1200',
            //'h' => '630'
        ];
        return $base . '?' . http_build_query($params);
    }

    public static function addBreakLine(string $text, int $amount = 22): string
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $candidate = $currentLine === ''
                ? $word
                : $currentLine . ' ' . $word;

            if (strlen($candidate) > $amount) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $candidate;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return implode("\n", $lines);
    }

    public static function getIndexJson(string $url): string
    {
        $string = self::_makeHttpRequest($url);
        return self::matchIndexJs($string);
    }

    public static function matchIndexJs(string $string): mixed
    {
        preg_match('/index-[A-Za-z0-9_-]+\.js/', $string, $matches);
        if (!isset($matches[0])) {
            throw new DetailedException('Index response: ' . $string);
        }
        return $matches[0];
    }

    private static function _makeHttpRequest(string $url): string
    {
        $cacheKey = '_cachedPage' . md5($url); // NOSONAR
        $cacheGroup = CacheGrp::DEFAULT;
        $res = Cache::read($cacheKey, $cacheGroup);
        if ($res) {
            return $res;
        }
        $http = new Client(['curl' => [CURLOPT_TIMEOUT_MS => 1200], 'redirect' => false]);

        $response = $http->get($url);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 500) {
            $stringBody = $response->getBody()->getContents();
        } else {
            throw new DetailedException('ex' . ($statusCode - 300));
        }
        if ($statusCode === 200) {
            Cache::write($cacheKey, $stringBody, $cacheGroup);
        }
        return $stringBody;
    }
}
