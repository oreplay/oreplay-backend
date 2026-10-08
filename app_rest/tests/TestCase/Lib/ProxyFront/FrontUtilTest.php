<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\ProxyFront;

use App\Lib\Consts\CacheGrp;
use App\Lib\ProxyFront\FrontUtil;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;

class FrontUtilTest extends TestCase
{
    use HttpClientTrait;

    private const string FRONT = 'https://front.example.com';

    public function setUp(): void
    {
        parent::setUp();
        Cache::delete('_frontIndexHtml', CacheGrp::DEFAULT);
    }

    private function _indexHtmlAsTheStaticHostServesIt(string $entry = 'index-BHdyZzfh.js'): string
    {
        return '<!doctype html>
<html lang="en" translate="no">
  <head><!--Inserte su codigo HTML a continuacion-->
<cors>
  <allowed-origins>
    <origin>https://www.oreplay.es</origin>
  </allowed-origins>
</cors>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/img/logo.png">
    <meta data-hid="image" itemprop="image" content="/img/logo.png">
    <meta data-hid="og:image" property="og:image" content="/img/logo.png">
    <meta data-hid="og:title" property="og:title" content="O-Replay">
    <meta data-hid="description" itemprop="description" content="O-Replay is the home">
    <meta data-hid="og:image:alt" property="og:image:alt" content="O-Replay is the home">
    <meta data-hid="og:description" property="og:description" content="O-Replay is the home">
    <title>O-Replay</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <script id="vite-plugin-pwa:register-sw" src="/registerSW.js"></script>
    <script type="module" crossorigin="" src="/assets/' . $entry . '"></script>
  </head>
  <body style="margin: 0; height: 100vh">
    <div style="min-height: 100vh" id="root"></div>
    <noscript>O-Replay is the home</noscript>
  </body>
</html>';
    }

    public function testBuildHtml(): void
    {
        $html = $this->_buildTrofeoPage();

        $description = '2026-10-05 Trofeo &quot;A&amp;B&quot; $1';
        $this->assertStringContainsString('<html lang="es" translate="no">', $html);
        $this->assertStringContainsString('itemprop="description" content="' . $description . '">', $html);
        $this->assertStringContainsString('property="og:image:alt" content="' . $description . '">', $html);
        $this->assertStringContainsString('property="og:description" content="' . $description . '">', $html);
        $this->assertStringContainsString('<noscript>' . $description . '</noscript>', $html);
        $this->assertStringContainsString(
            'property="og:image" content="https://or-img.gumlet.io/oreplay-og.png?sharp=false&amp;text=2026-10-05',
            $html
        );
        $this->assertStringContainsString('<script>window._ssr="0.4.18"</script></head>', $html);
    }

    public function testBuildHtml_shouldLeaveTheTagsTheFrontendOwnsUntouched(): void
    {
        $html = $this->_buildTrofeoPage();

        $this->assertStringContainsString('itemprop="image" content="/img/logo.png">', $html);
        $this->assertStringContainsString('property="og:title" content="O-Replay">', $html);
        $this->assertStringContainsString('<link rel="manifest" href="/manifest.webmanifest">', $html);
        $this->assertStringContainsString('src="/registerSW.js"></script>', $html);
        $this->assertStringContainsString('crossorigin="" src="/assets/index-BHdyZzfh.js"></script>', $html);
    }

    public function testBuildHtml_shouldRemoveTheStrayCorsBlockSoTheHeadKeepsItsTags(): void
    {
        $html = $this->_buildTrofeoPage();

        $this->assertStringNotContainsString('cors>', $html);
        $this->assertStringNotContainsString('Inserte', $html);
        $this->assertStringContainsString("<head><meta charset", preg_replace('/>\s+</', '><', $html));
    }

    private function _buildTrofeoPage(): string
    {
        return FrontUtil::buildHtml(
            $this->_indexHtmlAsTheStaticHostServesIt(),
            'es',
            '2026-10-05 Trofeo "A&B" $1',
            '0.4.18'
        );
    }

    public function testBuildHtml_shouldFallbackToEnglishOnInvalidLang(): void
    {
        $html = FrontUtil::buildHtml($this->_indexHtmlAsTheStaticHostServesIt(), '">', 'Home', '0.4.18');
        $this->assertStringContainsString('<html lang="en" translate="no">', $html);
    }

    public function testGetIndexHtml_shouldReuseTheCopyWhileFresh(): void
    {
        $this->mockClientGet(
            self::FRONT . '/index.html',
            $this->newClientResponse(200, [], $this->_indexHtmlAsTheStaticHostServesIt())
        );
        $this->mockClientGet(
            self::FRONT . '/index.html',
            $this->newClientResponse(200, [], $this->_indexHtmlAsTheStaticHostServesIt('index-NEW.js'))
        );

        $this->assertStringContainsString('index-BHdyZzfh.js', FrontUtil::getIndexHtml(self::FRONT . '/'));
        $this->assertStringContainsString('index-BHdyZzfh.js', FrontUtil::getIndexHtml(self::FRONT));
    }

    public function testGetIndexHtml_shouldAskAgainOnceTheCopyIsStale(): void
    {
        $this->mockClientGet(
            self::FRONT . '/index.html',
            $this->newClientResponse(200, [], $this->_indexHtmlAsTheStaticHostServesIt())
        );
        $this->mockClientGet(
            self::FRONT . '/index.html',
            $this->newClientResponse(200, [], $this->_indexHtmlAsTheStaticHostServesIt('index-NEW.js'))
        );

        FrontUtil::getIndexHtml(self::FRONT);
        $this->_ageTheStoredCopyBy(10);
        $this->assertStringContainsString('index-NEW.js', FrontUtil::getIndexHtml(self::FRONT));
        $this->assertStringContainsString('index-NEW.js', FrontUtil::getIndexHtml(self::FRONT));
    }

    public function testGetIndexHtml_shouldKeepTheLastBuildWhenTheHostFails(): void
    {
        $this->mockClientGet(
            self::FRONT . '/index.html',
            $this->newClientResponse(200, [], $this->_indexHtmlAsTheStaticHostServesIt())
        );
        $this->mockClientGet(self::FRONT . '/index.html', $this->newClientResponse(503, [], 'down'));
        $this->mockClientGet(self::FRONT . '/index.html', $this->newClientResponse(200, [], '<html>oops</html>'));

        FrontUtil::getIndexHtml(self::FRONT);
        $this->_ageTheStoredCopyBy(10);
        $this->assertStringContainsString('index-BHdyZzfh.js', FrontUtil::getIndexHtml(self::FRONT));
        $this->_ageTheStoredCopyBy(10);
        $this->assertStringContainsString('index-BHdyZzfh.js', FrontUtil::getIndexHtml(self::FRONT));
    }

    private function _ageTheStoredCopyBy(int $seconds): void
    {
        $stored = Cache::read('_frontIndexHtml', CacheGrp::DEFAULT);
        $stored['checkedAt'] -= $seconds;
        Cache::write('_frontIndexHtml', $stored, CacheGrp::DEFAULT);
    }

    public function testGetIndexHtml_shouldThrowWhenThereIsNoCopyToFallBackOn(): void
    {
        $this->mockClientGet(self::FRONT . '/index.html', $this->newClientResponse(200, [], '<html>oops</html>'));

        $this->expectException(DetailedException::class);
        FrontUtil::getIndexHtml(self::FRONT);
    }

    public function testGetOgImage(): void
    {
        $og = FrontUtil::getOgImage("Home for orienteering\nevents");
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = 'https://or-img.gumlet.io/oreplay-og.png?sharp=false&text=Home+for+orienteering%0Aevents&txt-size=42&text_color=%235e5c64&text_bg_color=%23ffffff&text_left=110&text_top=260&text_align=left&text_line_height=15';
        $this->assertEquals($expected, $og);
    }

    public function testAddBreakLine(): void
    {
        $this->assertEquals("Home for orienteering", FrontUtil::addBreakLine("Home for orienteering"));
        $this->assertEquals("Home for orienteering\nevents", FrontUtil::addBreakLine("Home for orienteering events"));
    }

    public function testMatchIndexJs(): void
    {
        $string = '<script type="module" crossorigin="" src="/assets/index-D_Zu-5II.js"></script>';
        $index = FrontUtil::matchIndexJs($string);
        $this->assertEquals('index-D_Zu-5II.js', $index);
    }

    public function testMatchIndexJs_shouldThrowException(): void
    {
        $string = '<script type="module" crossorigin="" src="/assets/ix-D_ZuC5II.js"></script>';
        $this->expectException(DetailedException::class);
        $this->expectExceptionMessage('Index response: ' . $string);
        FrontUtil::matchIndexJs($string);
    }
}
