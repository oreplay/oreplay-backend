<?php

declare(strict_types = 1);

namespace App\Lib\ProxyFront;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\Http\Client;
use RestApi\Lib\Exception\DetailedException;

class FrontUtil
{
    private const string INDEX_HTML_KEY = '_frontIndexHtml';
    private const int INDEX_FRESH_SECONDS = 10;

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

    /**
     * The index.html that the frontend build published, as the static host serves it right now.
     *
     * A page tolerates INDEX_FRESH_SECONDS of delay, which keeps a burst of visitors from becoming a burst of
     * requests to the static host, and a release reaches every page within that delay without being purged.
     * The service worker's own /index.html never reaches php: nginx proxies it beside sw.js, so both come from
     * the same build.
     */
    public static function getIndexHtml(string $url): string
    {
        $cached = Cache::read(self::INDEX_HTML_KEY, CacheGrp::DEFAULT) ?: null;
        if ($cached && time() - $cached['checkedAt'] < self::INDEX_FRESH_SECONDS) {
            return $cached['html'];
        }
        try {
            $html = self::_makeHttpRequest(rtrim($url, '/') . '/index.html');
            // an error page answered with 200 must not replace a page that works
            self::matchIndexJs($html);
        } catch (\Throwable $e) {
            if (!$cached) {
                throw $e;
            }
            // the static host is unreachable: keep serving the last build, and ask again later
            $html = $cached['html'];
        }
        Cache::write(self::INDEX_HTML_KEY, ['html' => $html, 'checkedAt' => time()], CacheGrp::DEFAULT);
        return $html;
    }

    /**
     * Fills the per-request values into the frontend's own index.html, so its <head> is defined in one place.
     */
    public static function buildHtml(string $html, string $lang, string $description, string $version): string
    {
        if (!preg_match('/^[a-z]{2}$/i', $lang)) {
            $lang = 'en';
        }
        $og = htmlspecialchars(self::getOgImage(self::addBreakLine($description)));
        $description = htmlspecialchars($description);
        $ssr = '<script>console.log("SSR v' . $version . '")</script>'
            . '<script>window._ssr="' . $version . '"</script>';

        // The static host puts an unknown <cors> element first in <head>, which ends the head for an html
        // parser and would leave every tag after it, og:* and the manifest link included, in the body.
        $html = self::_replace('/(?:<!--[^>]*-->\s*)?<cors>.*?<\/cors>\s*/s', '', $html);
        $html = self::_replace('/(<html\b[^>]*\blang=")[^"]*/', $lang, $html);
        $html = self::_setMetaContent($html, 'og:image', $og);
        $html = self::_setMetaContent($html, 'description', $description);
        $html = self::_setMetaContent($html, 'og:image:alt', $description);
        $html = self::_setMetaContent($html, 'og:description', $description);
        $html = self::_replace('/(<noscript>).*?(<\/noscript>)/s', $description, $html);
        return self::_replace('/(?=<\/head>)/', $ssr, $html);
    }

    private static function _setMetaContent(string $html, string $hid, string $content): string
    {
        $pattern = '/(<meta\b[^>]*\bdata-hid="' . preg_quote($hid, '/') . '"[^>]*\bcontent=")[^"]*/';
        return self::_replace($pattern, $content, $html);
    }

    /**
     * Replaces the first match with $insert, keeping what the pattern captured before and after it.
     * A callback rather than a replacement string, so a "$1" inside an event title stays literal.
     */
    private static function _replace(string $pattern, string $insert, string $html): string
    {
        $callback = fn(array $m) => ($m[1] ?? '') . $insert . ($m[2] ?? '');
        return preg_replace_callback($pattern, $callback, $html, 1) ?? $html;
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
        $http = new Client(['curl' => [CURLOPT_TIMEOUT_MS => 1200], 'redirect' => false]);

        $response = $http->get($url);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 500) {
            $stringBody = $response->getBody()->getContents();
        } else {
            throw new DetailedException('ex' . ($statusCode - 300));
        }
        return $stringBody;
    }
}
