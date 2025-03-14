<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Controller;

use App\Controller\ApiController;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use RestApi\TestSuite\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TokensFixture;

class CpiServerControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        TokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/radios/cpi/';
    }

    public function testAddNew()
    {
        // should process new radio punch
        $username = Stage::FIRST_STAGE;
        $password = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $data = [
            'order' => 'ProcessPunches',
            'data' => [$username, $password],
            'punches' => [
                [
                     'date' => '2025-03-08',
                     'raw' => '02d30d80160f85d41b01013c1e7400019db903',
                     'reading' => '2025-03-08 05:58:26',
                     'sicard' => '2009933',
                     'station' => '31',
                     'time' => '12:50',
                     'battery' => '9',
                     'type' => PunchType::SI_CARD
                ]
            ],
        ];

        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $punchAmount = 1;
        $expected = ['data' => ['OK', $punchAmount . '', '1']];
        $this->assertEquals($expected, $res);
    }

    public function testAddNew_shouldCheckMinimumEvent()
    {
        $username = Stage::FIRST_STAGE;
        $password = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $data = [
            'order' => 'CheckMinimumEventUser',
            'data' => [$username, $password],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => [Event::FIRST_EVENT, 'Test Foot-o', '00:00:00', '0', '', '0', $password, '']];
        $this->assertEquals($expected, $res);
    }

    public function testAddNew_shouldCheckMinimumEventErrorWithPassword()
    {
        $username = Stage::FIRST_STAGE;
        $password = TokensFixture::FIRST_TOKEN; // missing event token
        $data = [
            'order' => 'CheckMinimumEventUser',
            'data' => [$username, $password],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => ['-1', 'Use the secret and event token together as password', '', '', '', '', '', '']];
        $this->assertEquals($expected, $res);
    }

    public function testAddNew_shouldCheckMinimumEventErrorWithStageToken()
    {
        $username = Event::FIRST_EVENT; // bad stage token
        $password = TokensFixture::FIRST_TOKEN;
        $data = [
            'order' => 'CheckMinimumEventUser',
            'data' => [$username, $password],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => ['-1', 'Use the secret and event token together as password', '', '', '', '', '', '']];
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
