<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib;

use App\Lib\ApiFrozenTime;
use App\Lib\Consts\Languages;
use Cake\TestSuite\TestCase;

class ApiFrozenTimeTest extends TestCase
{
    public function testFrozenTimeFormatWithMilliseconds(): void
    {
        $time = new ApiFrozenTime('2014-07-06T13:09:01.523000+00:00');
        $this->assertEquals('2014-07-06 13:09:01.523000', $time->format('Y-m-d H:i:s.u'));
        $i18nFormat = $time->i18nFormat("yyyy-MM-dd'T'HH':'mm':'ss.SSSxxx", null, Languages::ENG);
        $expectedFormat = '2014-07-06T13:09:01.523+00:00';
        $this->assertEquals($expectedFormat, $i18nFormat);
        $this->assertEquals($expectedFormat, $time->jsonSerialize());
    }
}
