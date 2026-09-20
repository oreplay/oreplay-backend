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
        $this->assertEquals(['dg', 'xd', 'ml', 'db'], array_keys($bodyDecoded['data'][8]));
        $this->assertEquals('local', $bodyDecoded['data'][8]['db'],
            'the tests run against the development database, and the endpoint says which one is behind it');
        $this->assertEquals('on', $bodyDecoded['data'][8]['dg'],
            'a deploy that still runs with debug on is worth seeing from the outside');
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
