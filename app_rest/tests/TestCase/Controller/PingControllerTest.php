<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Controller\PingController;
use App\Lib\Consts\Languages;
use App\Lib\I18n\LegacyI18n;
use App\Test\Fixture\UsersFixture;

class PingControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        UsersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/ping/';
    }

    public function testGetData_gets()
    {
        $lang = Languages::ENG;
        LegacyI18n::setLocale($lang);
        $this->get($this->_getEndpoint() . PingController::SECRET . '?migrations=false');
        $this->assertJsonResponseOK();
        $bodyDecoded = json_decode($this->_getBodyAsString(), true);
        $this->assertEquals($lang, $bodyDecoded['data'][0]);
        $this->assertEquals('dev.example.com', $bodyDecoded['data'][1]);
        $this->assertEquals('use cache', $bodyDecoded['data'][3]);
    }

    public function testGetData_withoutSecret()
    {
        $this->get($this->_getEndpoint() . 'invalid');
        $this->assertResponseError($this->_getBodyAsString());
    }

    public function testAddNew()
    {
        $this->post($this->_getEndpoint(), ['hello' => 'world']);
        $this->assertResponseFailure($this->_getBodyAsString());
    }
}
