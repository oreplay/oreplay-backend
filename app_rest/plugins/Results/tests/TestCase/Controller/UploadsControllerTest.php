<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\AnswersTable;
use Results\Model\Table\ClassesControlsTable;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
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
use Results\Test\Fixture\TokensFixture;

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
    ];

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

        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleImportSmall()];
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
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith(' *** Updated 4 runners, 2 classes, 0 splits', $human);

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
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['start_time'],
                $value->getRunnerResults()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2014-07-06T10:09:14.523+00:00', $runnersJson[$key]['runner_results'][0]['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['id'],
                $value->getRunnerResults()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getRunnerResults()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 1, 4, 4);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldRequireAuthenticatedToken()
    {
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleImportSmall()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'runners' => 0,
            ],
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

        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleImportSmall()];
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'runners' => 0,
            ],
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

        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleSimpleFinishTime()];
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
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $expectedSplits = 3;
        $this->assertStringStartsWith(" *** Updated $expectedRunnerAmount runners, 1 classes, $expectedSplits splits", $human);

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
        $expectedControlAmount = 3;
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());

        // second upload should not add again results
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']));
        $this->_assertRunnersWithFinishTimes($decodedData, true);
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());
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
        $this->assertEquals(1, count($firstRunner['runner_results']), 'Amount of 1st runner_results');
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $firstRunner['id']])->all()->count());
        $this->assertEquals('Stage', $firstRunner['runner_results'][0]['result_type']['description']);
        $this->assertEquals('1', $firstRunner['runner_results'][0]['position']);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $firstRunner['runner_results'][0]['start_time']);
        $this->assertEquals('2024-09-29T12:26:54.000+00:00', $firstRunner['runner_results'][0]['finish_time']);
        $this->assertEquals(5214, $firstRunner['runner_results'][0]['time_seconds']);
        $this->assertEquals('0', $firstRunner['runner_results'][0]['status_code']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['time_behind']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['time_neutralization']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['time_adjusted']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['time_penalty']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['time_bonus']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['points_final']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['points_adjusted']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['points_penalty']);
        $this->assertEquals(0, $firstRunner['runner_results'][0]['points_bonus']);
        $this->assertEquals(1, $firstRunner['runner_results'][0]['leg_number']);
        if (!$skipSplits) {
            $this->assertEquals(2, count($firstRunner['runner_results'][0]['splits']));
            $this->assertEquals(31, $firstRunner['runner_results'][0]['splits'][0]['control']['station']);
            $this->assertEquals(1, $firstRunner['runner_results'][0]['splits'][0]['order_number']);
            $this->assertEquals('2024-01-28T10:15:05.000+00:00', $firstRunner['runner_results'][0]['splits'][0]['reading_time']);
            $this->assertEquals(33, $firstRunner['runner_results'][0]['splits'][1]['control']['station']);
            $this->assertEquals(2, $firstRunner['runner_results'][0]['splits'][1]['order_number']);
            $this->assertEquals('2024-01-28T10:18:37.000+00:00', $firstRunner['runner_results'][0]['splits'][1]['reading_time']);
        }
        $secondRunner = $decodedData[0]['runners'][1];
        $this->assertEquals('Velazquez', $secondRunner['last_name']);
        $this->assertEquals('105', $secondRunner['bib_number']);
        $this->assertEquals('4540555', $secondRunner['sicard']);
        $this->assertEquals('Independiente', $secondRunner['club']['short_name']);
        $this->assertEquals(1, count($secondRunner['runner_results']));
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $secondRunner['id']])->all()->count());
        $this->assertEquals('Stage', $secondRunner['runner_results'][0]['result_type']['description']);
        $this->assertEquals('2', $secondRunner['runner_results'][0]['position']);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $secondRunner['runner_results'][0]['start_time']);
        $this->assertEquals('2024-09-29T11:48:49.000+00:00', $secondRunner['runner_results'][0]['finish_time']);
        //$this->assertEquals(2929, $secondRunner['runner_results'][0]['time_seconds']);
        $this->assertEquals('0', $secondRunner['runner_results'][0]['status_code']);
        $this->assertEquals(44, $secondRunner['runner_results'][0]['time_behind']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['time_neutralization']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['time_adjusted']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['time_penalty']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['time_bonus']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['points_final']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['points_adjusted']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['points_penalty']);
        $this->assertEquals(0, $secondRunner['runner_results'][0]['points_bonus']);
        $this->assertEquals(1, $secondRunner['runner_results'][0]['leg_number']);
        if (!$skipSplits) {
            $this->assertArrayHasKey('splits', $secondRunner['runner_results'][0]);
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
        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleImport2CategoriesStarts()];
        $this->post($this->_getEndpoint() . '?version=301', $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->_assertStartsTimesFrom2Classes($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => UploadsControllerExamples::exampleImport2CategoriesSplits()];
        $this->post($this->_getEndpoint() . '?version=301', $data);
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
        //$this->assertStringStartsWith(' *** Updated 2 runners, 2 classes, 0 splits', $human);

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
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['start_time'],
                $value->getRunnerResults()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['runner_results'][0]['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['id'],
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
        //$this->assertStringStartsWith(' *** Updated 2 runners, 2 classes, 0 splits', $human);

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
                $resultId = $runnersJson[$key]['runner_results'][0]['id'];
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['runner_results'][0]['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['runner_results'][0]['id'],
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
        $this->assertEquals($teams, TeamsTable::load()->find()->all()->count());
        $this->assertEquals($teamsResults, TeamResultsTable::load()->find()->all()->count());
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
            'splits' => SplitsTable::load()->find()->all()->count() - 2,
            'controls' => ControlsTable::load()->find()->all()->count() - 1,
        ];
        $this->assertEquals($expected, $db, 'NewResultsTableAmounts');
    }
}
