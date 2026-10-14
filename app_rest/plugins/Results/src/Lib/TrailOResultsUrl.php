<?php

declare(strict_types = 1);

namespace Results\Lib;

class TrailOResultsUrl
{
    public const TOP_HOST = 'top.yq.cz';
    public const JARUORI_HOST = 'jaruori.es';
    public const ALLOWED_HOSTS = [
        self::TOP_HOST,
        self::JARUORI_HOST,
        'www.' . self::JARUORI_HOST,
    ];
    private const ALLOWED_SCHEMES = ['http', 'https'];

    public static function isAllowed(mixed $url): bool
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return in_array($scheme, self::ALLOWED_SCHEMES, true)
            && in_array($host, self::ALLOWED_HOSTS, true);
    }
}
