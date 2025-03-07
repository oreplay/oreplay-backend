<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Controller;

use App\Controller\ApiController;
use RestApi\TestSuite\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\TokensFixture;

class CpiServerControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        TokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/radios/cpi/';
    }

    public function testAddNew_shouldCheckMinimumEvent()
    {
        $username = Event::FIRST_EVENT;
        $password = TokensFixture::FIRST_TOKEN;
        $data = [
            'order' => 'CheckMinimumEventUser',
            'data' => [$username, $password],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => [$username, 'Test Foot-o', '', '', '', '', $password] ];
        $this->assertEquals($expected, $res);
    }

    public function testAddNew_shouldCheckConnectivity()
    {
        $data = [
            'order' => 'CheckConnectivity',
            'data' => [''],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => ['OK']];
        $this->assertEquals($expected, $res);
    }

    public function testAddNew_shouldThrowErrorWithInvalidParams()
    {
        $data = [
            'order' => 'invalid',
        ];
        $this->post($this->_getEndpoint(), $data);

        $this->assertResponseError();
    }
}
