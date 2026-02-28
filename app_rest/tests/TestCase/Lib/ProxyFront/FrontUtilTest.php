<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\ProxyFront;

use App\Lib\ProxyFront\FrontUtil;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;

class FrontUtilTest extends TestCase
{
    public function testMatchIndexJs(): void
    {
        $string = '<script type="module" crossorigin="" src="/assets/index-DZuC5II.js"></script>';
        $index = FrontUtil::matchIndexJs($string);
        $this->assertEquals('index-DZuC5II.js', $index);
    }

    public function testMatchIndexJs_shouldThrowException(): void
    {
        $string = '<script type="module" crossorigin="" src="/assets/ix-D_ZuC5II.js"></script>';
        $this->expectException(DetailedException::class);
        $this->expectExceptionMessage('Index response: ' . $string);
        FrontUtil::matchIndexJs($string);
    }
}
