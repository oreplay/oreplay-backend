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
        $base = 'https://textoverimg.com/wp-json/shakels/v1/image';
        $params = [
            'image' => 'http://d1ljmtj9ckzv64.cloudfront.net/oreplay-og.png',
            'text' => $text,
            'fontSize' => '42px',
            'fontColor' => '#5e5c64',
            'x_align' => '105',
            'y_align' => '260',
            'textAlign' => 'left',
            'margin' => '5',
        ];
        return $base . '?' . http_build_query($params);
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
