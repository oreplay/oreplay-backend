<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Controller;

use App\Controller\ApiController;
use Cake\I18n\FrozenTime;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use RestApi\TestSuite\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TokensFixture;

class CpiServerControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        ControlTypesFixture::LOAD,
        StagesFixture::LOAD,
        TokensFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
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
        $timezone = '+01:00';
        $data = [
            'order' => 'ProcessPunches',
            'data' => [$username, $password, $timezone],
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

        /** @var Split $last */
        $last = SplitsTable::load()->find()->orderDesc('created')->first();
        $expected = [
            'is_intermediate' => true,
            'reading_time' => new FrozenTime('2025-03-08 11:50:00.000000+00:00'),
            'points' => null,
            'order_number' => null,
        ];
        $split = $last->toArray();
        unset($split['created']);
        $this->assertEqualsNoId($expected, $split);
        $this->assertEquals(ClassEntity::ME, $last->class_id);
        $this->assertEquals(Runner::FIRST_RUNNER, $last->runner_id);
        $this->assertEquals(RunnerResult::FIRST_RES, $last->runner_result_id);
    }

    public function testAddNew_shouldStoreUnknownSiCard()
    {
        // should process new radio punch
        $username = Stage::FIRST_STAGE;
        $password = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $timezone = '+01:00';
        $data = [
            'order' => 'ProcessPunches',
            'data' => [$username, $password, $timezone],
            'punches' => [
                [
                    'date' => '2025-03-08',
                    'raw' => '02d30d80160f85d41b01013c1e7400019db903',
                    'reading' => '2025-03-08 05:58:26',
                    'sicard' => '1009232',
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

        /** @var Split $last */
        $last = SplitsTable::load()->find()->orderDesc('created')->first();
        $expected = [
            'is_intermediate' => true,
            'reading_time' => new FrozenTime('2025-03-08 11:50:00.000000+00:00'),
            'points' => null,
            'order_number' => null
        ];
        $split = $last->toArray();
        unset($split['created']);
        $this->assertEqualsNoId($expected, $split);
        $this->assertNull($last->class_id);
        $this->assertNull($last->runner_id);
        $this->assertNull($last->runner_result_id);
    }

    public function testAddNew_shouldCheckMinimumEvent()
    {
        $username = Stage::FIRST_STAGE;
        $password = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $timezone = '+01:00';
        $data = [
            'order' => 'CheckMinimumEventUser',
            'data' => [$username, $password, $timezone],
            'punches' => [],
        ];
        $this->post($this->_getEndpoint(), $data);

        $res = $this->assertJsonResponseOK();
        $expected = ['data' => [
            Stage::FIRST_STAGE, 'Test Foot-o (First stage)', '00:00:00', '0', '', '0', $password, '']];
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
