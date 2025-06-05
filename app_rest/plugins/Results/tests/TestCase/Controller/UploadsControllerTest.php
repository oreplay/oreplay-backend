<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Cake\I18n\FrozenTime;
use Results\Controller\UploadsController;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\StageType;
use Results\Model\Entity\Team;
use Results\Model\Table\AnswersTable;
use Results\Model\Table\ClassesControlsTable;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\CoursesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\TestCase\Controller\UploadExamples\IntermediateExamples;
use Results\Test\TestCase\Controller\UploadExamples\RelayExamples;
use Results\Test\TestCase\Controller\UploadExamples\ResultExamples;
use Results\Test\TestCase\Controller\UploadExamples\StartExamples;
use Results\Test\TestCase\Controller\UploadExamples\TotalsExamples;

class UploadsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        ResultTypesFixture::LOAD,
        RunnerResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
        TokensFixture::LOAD,
        StageTypesFixture::LOAD,
        CoursesFixture::LOAD,
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
    ];

    const PREFIX = ' *** PLEASE UPDATE THE DESKTOP CLIENT TO THE LAST VERSION!!!!!!!!!!!!!!!!!!!!!';

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/';
    }

    public function testAddNew_shouldAddStartTimes()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::startImportSmall()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
            ],
            'humanColor' => '#075210',
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith(self::PREFIX . ' Updated 4 participants, 2 classes, 0 splits', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $this->assertEquals(2, count($decodedData[0]['runners']));
        $this->assertEquals(2, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['sex'] ?? null, $value->sex);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $stage = $runnersJson[$key]['stage'];
            $this->assertEquals(UploadTypes::START_LIST, $stage['upload_type']);
            $this->assertEquals($stage['status_code'],
                $value->getRunnerResults()[0]->status_code);
            $this->assertEquals($stage['start_time'],
                $value->getRunnerResults()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2014-07-06T10:09:14.523+00:00', $stage['start_time']);
            }
            $this->assertEquals($stage['id'],
                $value->getRunnerResults()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getRunnerResults()[0]->result_type_id);
        }
        $this->assertEquals('F', $runnersJson[0]['sex']);
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 1, 4, 4);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldAddStartTimesWithTeams()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::startTimesWithOneRunnerAndOneTeam()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
            ],
            'humanColor' => '#075210',
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith(self::PREFIX . ' Updated 4 participants, 2 classes, 0 splits', $human);

        $dbTeams = TeamsTable::load()->find()
            ->where(['created >' => new FrozenTime('-1 minute')])
            ->contain(TeamResultsTable::name())->all();
        $this->assertEquals(1, $dbTeams->count());
        /** @var Team $firstTeam */
        $firstTeam = $dbTeams->first();
        $this->assertEquals('Couupless', $firstTeam->team_name);
        $this->assertEquals('2024-11-10T09:30:00+00:00', $firstTeam->_getStage()->start_time->toIso8601String());

        $addedClasses = $ClassesTable->find()
            ->where([
                'Classes.stage_id' => StagesFixture::STAGE_FEDO_2,
                'Classes.created >' => new FrozenTime('-1 minute')
            ])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['Individual', 'DUAL.TEAM'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->where(['Runners.created >' => new FrozenTime('-1 minute')])
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(3, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['teams']));
        $this->assertEquals(2, count($decodedData[1]['teams'][0]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['teams'][0]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $currentRunner = $runnersJson[$key];
            $this->assertEquals($currentRunner['last_name'], $value->last_name);
            $this->assertEquals($currentRunner['first_name'], $value->first_name);
            $this->assertEquals($currentRunner['sicard'], $value->sicard);
            $this->assertEquals($currentRunner['id'], $value->id);
            $this->assertEquals($currentRunner['club']['short_name'], $value->club->short_name);
            $stage = $currentRunner['stage'];
            $this->assertEquals(UploadTypes::START_LIST, $stage['upload_type']);
            $this->assertEquals($stage['start_time'],
                $value->getRunnerResults()[0]->start_time->jsonSerialize());
            $this->assertEquals('2024-11-10T09:30:00.000+00:00', $stage['start_time']);
            $this->assertEquals($stage['id'],
                $value->getRunnerResults()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getRunnerResults()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 1, 1, 0);
        $this->_assertNewBasicTables(2, 2, 2, 3, 3);
        $this->_assertNewResultsTables(0, 0);
        // check uploaded teams
        $dbTeams = TeamsTable::load()
            ->findTeamsInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->toArray();
        $this->assertEquals(1, count($dbTeams));
        $this->assertEquals('Couupless', $dbTeams[0]['team_name']);
        /** @var FrozenTime $start_time */
        $start_time = $dbTeams[0]['team_results'][0]['start_time'];
        $this->assertEquals('2024-11-10T09:30:00+00:00', $start_time->toIso8601String());
        $this->assertEquals(2, count($dbTeams[0]['runners']));
        $this->assertEquals('Morenoa', $dbTeams[0]['runners'][0]['last_name']);
        $this->assertEquals('Ponceb', $dbTeams[0]['runners'][1]['last_name']);
    }

    public function testAddNew_shouldAddIntermediatesWithRadios()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => IntermediateExamples::intermediateResults()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 2,
                'courses' => 1,
                'splits' => 4,
                'runnerResults' => 2,
            ],
            'humanColor' => '#075210',
        ];
        unset($jsonDecoded['meta']['human']);
        unset($jsonDecoded['meta']['timings']);
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringContainsString('Updated 1 classes, 1 courses', $human);

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->order(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals(4, $dbSplits->count());
        /** @var Split $splitA */
        $splitA = $dbSplits->first();
        $this->assertEquals(true, $splitA->is_intermediate);
        $this->assertEquals(1, $splitA->order_number);
        $this->assertEquals(4, substr_count($splitA->class_id, '-'));
        $this->assertEquals(32, $splitA->control->station);
        /** @var Split $splitB */
        $splitB = $dbSplits->last();
        $this->assertEquals(true, $splitB->is_intermediate);
        $this->assertEquals(2, $splitB->order_number);
        $this->assertEquals(4, substr_count($splitA->class_id, '-'));
        $this->assertEquals(100, $splitB->control->station);
    }

    public function testAddNew_shouldRequireAuthenticatedToken()
    {
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::startImportSmall()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'runners' => 0,
            ],
            'humanColor' => '#FF0000',
            'human' => [
                "\n    [ERROR - 403] ($now) ForbiddenException \n"
            ]
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);

    }

    public function testAddNew_shouldNotUpdateStartListWhenThereAreFinishTimes()
    {
        Cache::clear();
        RunnerResultsTable::load()->updateAll([
            'stage_id' => StagesFixture::STAGE_FEDO_2,
            'finish_time' => new FrozenTime()
        ], ['id' => RunnerResult::FIRST_RES]);
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::startImportSmall()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'runners' => 0,
            ],
            'humanColor' => '#FF0000',
            'human' => [
                "\n    [ERROR - 400] ($now) Cannot add start times when there are already finish times \n"
            ]
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(1, count($addedClasses));

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(0, count($res), 'Runner count in db');
    }

    public function testAddNew_shouldAddFinishTimes()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $expectedRunnerAmount = 2;
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => $expectedRunnerAmount,
            ],
            'humanColor' => '#075210',
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $expectedSplits = 3;
        $this->assertStringStartsWith(self::PREFIX . " Updated $expectedRunnerAmount participants, 1 classes, $expectedSplits splits", $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', '10 Mas30F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']));
        $this->_assertRunnersWithFinishTimes($decodedData);
        $expectedControlAmount = $this->controlsAmount() + 2;
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());

        // second upload should not add again results
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData), json_encode($decodedData));
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']), json_encode($decodedData));
        $this->_assertRunnersWithFinishTimes($decodedData, true);
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->order(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals($expectedSplits, $dbSplits->count());
        /** @var Split $splitA */
        $splitA = $dbSplits->first();
        $this->assertEquals(false, $splitA->is_intermediate);
        $this->assertEquals(1, $splitA->order_number);
        $this->assertEquals(31, $splitA->control->station);
    }

    private function _assertRunnersWithFinishTimes($decodedData, $skipSplits = false)
    {
        $Table = RunnerResultsTable::load();
        $this->assertEquals(1, count($decodedData));
        $this->assertEquals(2, count($decodedData[0]['runners']));
        $firstRunner = $decodedData[0]['runners'][0];
        $this->assertEquals('Ballesteros', $firstRunner['last_name']);
        $this->assertEquals('125', $firstRunner['bib_number']);
        $this->assertEquals('4440522', $firstRunner['sicard']);
        $this->assertEquals('Independiente', $firstRunner['club']['short_name']);
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $firstRunner['id']])->all()->count());
        $stage = $firstRunner['stage'];
        $this->assertEquals('Stage', $stage['result_type']['description']);
        $this->assertEquals('1', $stage['position']);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $stage['start_time']);
        $this->assertEquals('2024-09-29T12:26:54.000+00:00', $stage['finish_time']);
        $this->assertEquals(5214, $stage['time_seconds']);
        $this->assertEquals('0', $stage['status_code']);
        $this->assertEquals(UploadTypes::FINISH_TIMES, $stage['upload_type']);
        $this->assertEquals(0, $stage['time_behind']);
        $this->assertEquals(0, $stage['time_neutralization']);
        $this->assertEquals(0, $stage['time_adjusted']);
        $this->assertEquals(0, $stage['time_penalty']);
        $this->assertEquals(0, $stage['time_bonus']);
        $this->assertEquals(0, $stage['points_final']);
        $this->assertEquals(0, $stage['points_adjusted']);
        $this->assertEquals(0, $stage['points_penalty']);
        $this->assertEquals(0, $stage['points_bonus']);
        $this->assertEquals(1, $stage['leg_number']);
        if (!$skipSplits) {
            $this->assertEquals(2, count($stage['splits']));
            $this->assertEquals(31, $stage['splits'][0]['control']['station']);
            $this->assertEquals(1, $stage['splits'][0]['order_number']);
            $this->assertEquals('2024-01-28T10:15:05.000+00:00', $stage['splits'][0]['reading_time']);
            $this->assertEquals(33, $stage['splits'][1]['control']['station']);
            $this->assertEquals(2, $stage['splits'][1]['order_number']);
            $this->assertEquals('2024-01-28T10:18:37.000+00:00', $stage['splits'][1]['reading_time']);
        }
        $secondRunner = $decodedData[0]['runners'][1];
        $this->assertEquals('Velazquez', $secondRunner['last_name']);
        $this->assertEquals('105', $secondRunner['bib_number']);
        $this->assertEquals('4540555', $secondRunner['sicard']);
        $this->assertEquals('Independiente', $secondRunner['club']['short_name']);
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $secondRunner['id']])->all()->count());
        $stage = $secondRunner['stage'];
        $this->assertEquals('Stage', $stage['result_type']['description']);
        $this->assertEquals('2', $stage['position']);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $stage['start_time']);
        $this->assertEquals('2024-09-29T11:48:49.000+00:00', $stage['finish_time']);
        $this->assertEquals(UploadTypes::FINISH_TIMES, $stage['upload_type']);
        //$this->assertEquals(2929, $secondRunner['stage']['time_seconds']);
        $this->assertEquals('0', $stage['status_code']);
        $this->assertEquals(44, $stage['time_behind']);
        $this->assertEquals(0, $stage['time_neutralization']);
        $this->assertEquals(0, $stage['time_adjusted']);
        $this->assertEquals(0, $stage['time_penalty']);
        $this->assertEquals(0, $stage['time_bonus']);
        $this->assertEquals(0, $stage['points_final']);
        $this->assertEquals(0, $stage['points_adjusted']);
        $this->assertEquals(0, $stage['points_penalty']);
        $this->assertEquals(0, $stage['points_bonus']);
        $this->assertEquals(1, $stage['leg_number']);
        if (!$skipSplits) {
            $this->assertArrayHasKey('splits', $stage);
        }
    }

    public function testAddNew_shouldAddStartsAndSplits()
    {
        Cache::clear();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => ResultExamples::resultImport2CategoriesStarts()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertStartsTimesFrom2Classes($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => ResultExamples::resultImport2CategoriesSplits()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertSplitsTimesFrom2Classes($jsonDecoded);
    }

    private function _assertStartsTimesFrom2Classes($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 2,
            'runners' => 2,
            'courses' => 2,
            'splits' => 0,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 2 classes, 2 courses (', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(3, count($addedClasses));
        $expectedClasses = ['ME', 'U-10', 'O ROJO F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(2, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $this->assertEquals($runnersJson[$key]['stage']['start_time'],
                $value->getRunnerResults()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['stage']['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['stage']['id'],
                $value->getRunnerResults()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getRunnerResults()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 2, 2, 2);
        $this->_assertNewResultsTables(0, 0);
    }

    private function _assertSplitsTimesFrom2Classes($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();

        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 2,
            'runners' => 2,
            'courses' => 2,
            'splits' => 2,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        //$this->assertStringStartsWith(self::PREFIX . ' Updated 2 participants, 2 classes, 0 splits', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(3, count($addedClasses));
        $expectedClasses = ['ME', 'U-10', 'O ROJO F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(2, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            if ($key === 0) {
                $resultId = $runnersJson[$key]['stage']['id'];
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['stage']['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['stage']['id'],
                $value->getRunnerResults()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getRunnerResults()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 2, 2, 2);
        $this->_assertNewResultsTables(2, 1);
        /** @var RunnerResult $res */
        $res = RunnerResultsTable::load()->get($resultId);
        $this->assertEquals('"2024-10-18T09:56:00.000+00:00"', json_encode($res->start_time));
        $this->assertEquals('"2024-10-18T10:09:40.000+00:00"', json_encode($res->finish_time));
        $this->assertEquals(0, $res->time_behind);
        $this->assertEquals(820, $res->time_seconds);
        $this->assertEquals('bab8412b5a99d7b26e7a645c7caa9244', $res->upload_hash);
    }

    private function _assertNewOptionalTables($classesControls, $teams, $teamsResults, $answers): void
    {
        $this->assertEquals($classesControls, ClassesControlsTable::load()->find()->all()->count());
        $this->assertEquals($teams + 1, TeamsTable::load()->find()->all()->count(), 'Teams');
        $this->assertEquals($teamsResults + 1, TeamResultsTable::load()->find()->all()->count(), 'TeamResults');
        $this->assertEquals($answers, AnswersTable::load()->find()->all()->count());
    }

    private function _assertNewBasicTables($clubs, $courses, $classes, $runners, $runnerResults): void
    {
        $expected = [
            'clubs' => $clubs,
            'courses' => $courses,
            'classes' => $classes,
            'runners' => $runners,
            'runnerResults' => $runnerResults,
        ];
        $db = [
            'clubs' => ClubsTable::load()->find()->all()->count() - 1,
            'courses' => CoursesTable::load()->find()->all()->count() - 1,
            'classes' => ClassesTable::load()->find()->all()->count() - 2,
            'runners' => RunnersTable::load()->find()->all()->count() - 2,
            'runnerResults' => RunnerResultsTable::load()->find()->all()->count() - 1,
        ];
        $this->assertEquals($expected, $db, 'NewBasicTableAmounts');
    }

    private function _assertNewResultsTables($splits, $controls): void
    {
        $expected = [
            'splits' => $splits,
            'controls' => $controls,
        ];
        $db = [
            'splits' => SplitsTable::load()->find()->all()->count() - $this->splitsAmount(),
            'controls' => ControlsTable::load()->find()->all()->count() - $this->controlsAmount(),
        ];
        $this->assertEquals($expected, $db, 'NewResultsTableAmounts');
    }

    private function splitsAmount(): int
    {
        $fixture = new SplitsFixture();
        return count($fixture->records);
    }

    private function controlsAmount(): int
    {
        $fixture = new ControlsFixture();
        return count($fixture->records);
    }

    public function testAddNew_shouldAddRelayResultsWithoutSplits()
    {
        Cache::clear();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => RelayExamples::simple3relay()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertSimple3relay($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $dataTransfer = RelayExamples::simple3relay();
        $dataTransfer['event']['stages'][0]['classes'][0]['teams'][0]['team_results'][0]['time_seconds'] = 3601;
        $data = ['oreplay_data_transfer' => $dataTransfer];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertSimple3relay($jsonDecoded);
    }

    private function _assertSimple3relay($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 1,
            'runners' => 4,
            'courses' => 1,
            'splits' => 0,
            'runnerResults' => 4,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 1 classes, 1 courses (', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'SENIOR FEM'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        $this->assertEquals(3, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['teams']));
        $this->assertEquals(3, count($decodedData[0]['teams'][0]['runners']));
        $this->assertEquals(1, $decodedData[0]['teams'][0]['runners'][0]['leg_number'] ?? null);
        $this->assertEquals(2, $decodedData[0]['teams'][0]['runners'][1]['leg_number'] ?? null);
        $this->assertEquals(3, $decodedData[0]['teams'][0]['runners'][2]['leg_number'] ?? null);
//        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
//        /** @var Runner $value */
//        foreach ($res as $key => $value) {
//            $this->assertEquals($runnersJson[$key]['last_name'], $value->last_name);
//            $this->assertEquals($runnersJson[$key]['first_name'], $value->first_name);
//            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
//            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
//            $this->assertEquals($runnersJson[$key]['id'], $value->id);
//            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
//            $this->assertEquals($runnersJson[$key]['stage']['start_time'],
//                $value->getRunnerResults()[0]->start_time->jsonSerialize());
//            if ($key === 0) {
//                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['stage']['start_time']);
//            }
//            $this->assertEquals($runnersJson[$key]['stagestage']['id'],
//                $value->getRunnerResults()[0]->id);
//            $this->assertEquals(ResultType::STAGE,
//                $value->getRunnerResults()[0]->result_type_id);
//        }
        $this->_assertNewOptionalTables(0, 1, 1, 0);
        $this->_assertNewBasicTables(1, 1, 1, 3, 3);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldAddTotalsWithPoints()
    {
        Cache::clear();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => TotalsExamples::simpleTotalPoints()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertTotals($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $dataTransfer = TotalsExamples::simpleTotalPoints(2932);
        $data = ['oreplay_data_transfer' => $dataTransfer];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertTotals($jsonDecoded);
    }

    private function _assertTotals($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 1,
            'runners' => 2,
            'courses' => 1,
            'splits' => 0,
            'runnerResults' => 6,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Result type STAGE converted to PARTIAL_OVERALL</b>) 1 classes, 1 courses (0', $human);

        $newStage = StagesTable::load()->find()->orderDesc('created')->firstOrFail();
        $this->assertEquals(StageType::TOTALS, $newStage->stage_type_id);
        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => $newStage->id])
            ->contain(CoursesTable::name())
            ->orderAsc('Classes.oe_key')
            ->all();
        $expectedClasses = ['F-E'];
        $this->assertEquals(count($expectedClasses), count($addedClasses));
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, $newStage->id)
            ->orderAsc('last_name')
            ->all();
        $this->assertEquals(2, count($res), 'Runner count in db');

        $this->assertEquals(0, count($decodedData[0]['teams']));
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 1, 1, 2, 6);
        $this->_assertNewResultsTables(0, 0);
    }
}
