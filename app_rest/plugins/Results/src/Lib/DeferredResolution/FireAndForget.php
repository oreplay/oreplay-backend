<?php

declare(strict_types = 1);

namespace Results\Lib\DeferredResolution;

use Cake\Http\Exception\InternalErrorException;

class FireAndForget
{
    public static function postJson(string $url, array $data): void
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
            throw new InternalErrorException('Error opening fsockopen ' . $errno);
        }

        $headers = "POST {$path} HTTP/1.1\r\n";
        $headers .= "Host: {$host}\r\n";
        $headers .= "Content-Type: application/json\r\n";
        $headers .= "Content-Length: {$contentLength}\r\n";
        $headers .= "Connection: Close\r\n\r\n";

        $out = $headers . $jsonData;

        fwrite($fp, $out);

        //$response = '';
        //while (!feof($fp)) {
        //    $response .= fgets($fp, 1024);
        //}
        //debug($response);

        fclose($fp);
    }
}
