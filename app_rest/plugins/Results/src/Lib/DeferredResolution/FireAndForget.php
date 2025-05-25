<?php

declare(strict_types = 1);

namespace Results\Lib\DeferredResolution;

use Cake\Http\Exception\InternalErrorException;

class FireAndForget
{
    public static function postJson(string $url, array $data, array $extraHeaders = []): void
    {
        $parts = parse_url($url);
        $host = $parts['host'];
        $port = $parts['scheme'] === 'https' ? 443 : 80;
        $path = $parts['path'] ?? '/';
        if (isset($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        $jsonData = json_encode($data);
        $contentLength = strlen($jsonData);

        $isSsl = ($parts['scheme'] === 'https');
        $fp = fsockopen(($isSsl ? 'ssl://' : '') . $host, $port, $errno, $errstr, 30);
        if (!$fp) {
            throw new InternalErrorException("Error opening connection with fsockopen: $errstr ($errno)");
        }

        $headers = self::_getHeaderString($path, $host, $contentLength, $extraHeaders);
        $out = $headers . $jsonData;

        fwrite($fp, $out);

        //$response = '';
        //while (!feof($fp)) {
        //    $response .= fgets($fp, 1024);
        //}
        //debug($response);

        fclose($fp);
    }

    private static function _getHeaderString(mixed $path, mixed $host, int $contentLength, array $extraHeaders): string
    {
        $headers = [
            "POST {$path} HTTP/1.1",
            "Host: {$host}",
            "Content-Type: application/json",
            "Content-Length: {$contentLength}",
            "Connection: Close"
        ];

        foreach ($extraHeaders as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        return implode("\r\n", $headers) . "\r\n\r\n";
    }
}
