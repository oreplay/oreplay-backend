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
        $expected = 'https://textoverimg.com/wp-json/shakels/v1/image?image=http%3A%2F%2Fd1ljmtj9ckzv64.cloudfront.net%2Foreplay-og.png&text=Home+for+orienteering%0Aevents&fontSize=42px&fontColor=%235e5c64&x_align=105&y_align=260&textAlign=left&margin=5';
        $this->assertEquals($expected, $og);
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
