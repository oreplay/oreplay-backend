<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\ProxyFront;

use App\Lib\ProxyFront\FrontUtil;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;

class FrontUtilTest extends TestCase
{
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
