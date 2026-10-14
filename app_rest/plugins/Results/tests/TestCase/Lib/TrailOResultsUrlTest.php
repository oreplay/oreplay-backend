<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Results\Lib\TrailOResultsUrl;

class TrailOResultsUrlTest extends TestCase
{
    public static function allowedUrls(): array
    {
        return [
            'TOP' => ['https://top.yq.cz/event/123/results'],
            'Jaruori' => ['https://jaruori.es/eventos/2021xermade/index.html'],
            'Jaruori with www and http' => ['http://www.jaruori.es/eventos/goente2020/index.html'],
            'uppercase host' => ['https://TOP.YQ.CZ/event/1'],
        ];
    }

    #[DataProvider('allowedUrls')]
    public function testIsAllowed_acceptsTopAndJaruoriUrls(string $url): void
    {
        $this->assertTrue(TrailOResultsUrl::isAllowed($url));
    }

    public static function rejectedUrls(): array
    {
        return [
            'another host' => ['https://www.oreplay.es/results'],
            'host that only ends like an allowed one' => ['https://evil-jaruori.es/results'],
            'subdomain of an allowed host' => ['https://fake.top.yq.cz/results'],
            'allowed host in the path' => ['https://example.com/top.yq.cz'],
            'not http' => ['ftp://top.yq.cz/results'],
            'javascript' => ['javascript://top.yq.cz/%0Aalert(1)'],
            'not a url' => ['top.yq.cz/results'],
            'empty' => [''],
            'not a string' => [123],
        ];
    }

    #[DataProvider('rejectedUrls')]
    public function testIsAllowed_rejectsAnyOtherUrl(mixed $url): void
    {
        $this->assertFalse(TrailOResultsUrl::isAllowed($url));
    }
}
