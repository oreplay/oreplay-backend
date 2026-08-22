<?php

declare(strict_types = 1);

namespace RadioRelay\Test\TestCase\Controller;

use App\Controller\ApiController;
use Cake\I18n\FrozenTime;
use RadioRelay\Lib\Cpi\Consts\PunchType;
use RestApi\TestSuite\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\ControlsTable;
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
    protected array $fixtures = [
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
        $last = SplitsTable::load()->find()->orderByDesc('created')->first();
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
        $this->assertEquals(['31'], $this->_intermediateStations(),
            'a punch arriving straight from the radio hardware flags its station too');
    }

    public function testAddNew_shouldFlagAStationStoredBeforeTheRadioEverPunchedIt()
    {
        $controls = ControlsTable::load();
        $storedByAnEarlierUpload = $controls->fillNewWithStage(
            ['station' => '31'],
            Event::FIRST_EVENT,
            Stage::FIRST_STAGE
        );
        $storedByAnEarlierUpload->control_type_id = ControlType::NORMAL;
        $storedByAnEarlierUpload->is_intermediate = false;
        $controls->saveOrFail($storedByAnEarlierUpload);

        $data = [
            'order' => 'ProcessPunches',
            'data' => [Stage::FIRST_STAGE, Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN, '+01:00'],
            'punches' => [[
                'date' => '2025-03-08',
                'raw' => '02d30d80160f85d41b01013c1e7400019db903',
                'reading' => '2025-03-08 05:58:26',
                'sicard' => '2009933',
                'station' => '31',
                'time' => '12:50',
                'battery' => '9',
                'type' => PunchType::SI_CARD,
            ]],
        ];
        $this->post($this->_getEndpoint(), $data);
        $this->assertJsonResponseOK();

        $this->assertTrue($controls->get($storedByAnEarlierUpload->id)->is_intermediate,
            'the row an earlier upload stored is the one the read path will find, so it must be flagged');
    }

    public function testAddNew_shouldDiscardAPunchFromAnUnknownSiCard()
    {
        $username = Stage::FIRST_STAGE;
        $password = Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN;
        $timezone = '+01:00';
        $storedBefore = SplitsTable::load()->find()->all()->count();
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

        // the device is still told its punch arrived: it cannot do anything about an unknown chip, and
        // failing the request would make it retry the same punch for ever
        $res = $this->assertJsonResponseOK();
        $this->assertEquals(['data' => ['OK', '1', '1']], $res);

        // a split with no runner, class or result belongs to nobody and nothing ever reconnects it
        $this->assertEquals($storedBefore, SplitsTable::load()->find()->all()->count());
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

    public function testAddNew_shouldReuseTheControlAnEarlierUploadStored()
    {
        $controls = ControlsTable::load();
        $stored = $controls->fillNewWithStage(['station' => '31'], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $stored->control_type_id = ControlType::NORMAL;
        $stored->is_intermediate = false;
        $controls->saveOrFail($stored);

        $this->post($this->_getEndpoint(), $this->_punchAtStation31());
        $this->assertJsonResponseOK();

        $this->assertEquals(1, $this->_controlAmountAtStation('31'),
            'the radio punched a station that already had a control, so no second row belongs there');
        /** @var Split $last */
        $last = SplitsTable::load()->find()->orderByDesc('created')->first();
        $this->assertEquals($stored->id, $last->control_id,
            'the punch points at the stored control, the one every other query already joins');
    }

    private function _punchAtStation31(): array
    {
        return [
            'order' => 'ProcessPunches',
            'data' => [Stage::FIRST_STAGE, Event::FIRST_EVENT . TokensFixture::FIRST_TOKEN, '+01:00'],
            'punches' => [[
                'date' => '2025-03-08',
                'raw' => '02d30d80160f85d41b01013c1e7400019db903',
                'reading' => '2025-03-08 05:58:26',
                'sicard' => '2009933',
                'station' => '31',
                'time' => '12:50',
                'battery' => '9',
                'type' => PunchType::SI_CARD,
            ]],
        ];
    }

    private function _controlAmountAtStation(string $station): int
    {
        return ControlsTable::load()->find()
            ->where(['stage_id' => Stage::FIRST_STAGE, 'station' => $station])
            ->all()->count();
    }

    private function _intermediateStations(): array
    {
        return ControlsTable::load()->find()
            ->where(['stage_id' => Stage::FIRST_STAGE, 'is_intermediate' => true])
            ->all()->extract('station')->toList();
    }
}
