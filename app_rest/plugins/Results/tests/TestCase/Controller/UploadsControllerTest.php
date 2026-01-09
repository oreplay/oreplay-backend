<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Results\Controller\UploadsController;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\UploadConfigChecker;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;
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
use Results\Test\TestCase\Controller\UploadExamples\MixedExamples;
use Results\Test\TestCase\Controller\UploadExamples\RelayExamples;
use Results\Test\TestCase\Controller\UploadExamples\ResultExamples;
use Results\Test\TestCase\Controller\UploadExamples\StartExamples;
use Results\Test\TestCase\Controller\UploadExamples\TotalsExamples;

class UploadsControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
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

    protected function _getEndpointAddingToSwagger(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/';
    }

    protected function _getEndpoint(): string
    {
        $this->skipNextRequestInSwagger();
        return $this->_getEndpointAddingToSwagger();
    }

    public function testAddNew_onError()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = [
            '_c' => 'UploadPostData',
            'oreplay_data_transfer' => [
                '_c' => 'UploadDataTransfer',
                'configuration' => [
                    'source_vendor' => 'sportSoftware',
                    'source' => 'OE2010',
                    'source_version' => '12.2',
                    'contents' => 'StartList | ResultList',
                    'results_type' => UploadConfigChecker::TYPE_MIXED,
                    'utf' => true,
                ],
                'event' => [
                    'id' => Event::FIRST_EVENT,
                    'description' => 'Demo - 5 days of Italy 2014',
                    'stages' => []
                ]
            ]
        ];
        $this->post($this->_getEndpointAddingToSwagger() . '?version=501', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $expected = [
            '_c' => 'Uploaded',
            'meta' => [
                '_c' => 'UploadedMeta',
                'updated' => [
                    'classes' => 0,
                    'runners' => 0,
                ],
                'humanColor' => '#FF0000',
                'human' => []
            ],
            'data' => []
        ];
        $jsonDecoded['meta']['human'] = [];
        $this->assertEquals($expected, $jsonDecoded);
    }

    public function testAddNew_shouldDecodeGzip()
    {
        Cache::clear();
        //$this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = [
            '_c' => 'UploadPostData',
            'oreplay_data_transfer' => [
                '_c' => 'UploadDataTransfer',
                'configuration' => [
                    'source_vendor' => 'sportSoftware',
                    'source' => 'OE2010',
                    'source_version' => '12.2',
                    'contents' => 'StartList | ResultList',
                    'results_type' => UploadConfigChecker::TYPE_MIXED,
                    'utf' => true,
                ],
                'event' => [
                    'id' => Event::FIRST_EVENT,
                    'description' => 'Demo - 5 days of Italy 2014',
                    'stages' => []
                ]
            ]
        ];

        $json = json_encode($data);
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
                'Authorization' => 'Bearer ' . TokensFixture::FIRST_TOKEN
            ],
            'input' => gzencode($json),
        ]);
        $this->post($this->_getEndpoint() . '?version=501');

        $jsonDecoded = $this->assertJsonResponseOK();
        $expected = [
            '_c' => 'Uploaded',
            'meta' => [
                '_c' => 'UploadedMeta',
                'updated' => [
                    'classes' => 0,
                    'runners' => 0,
                ],
                'humanColor' => '#FF0000',
                'human' => []
            ],
            'data' => []
        ];
        $jsonDecoded['meta']['human'] = [];
        $this->assertEquals($expected, $jsonDecoded);
    }

    public function testAddNew_shouldAddMixedContent()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => MixedExamples::importMixed()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 6,
                'courses' => 2,
                'splits' => 3,
                'runnerResults' => 6,
            ],
            'humanColor' => '#075210',
            'human' => [''],
            'timings' => [],
        ];
        $jsonDecoded['meta']['timings'] = [];
        unset($jsonDecoded['meta']['human'][1]);
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith('Updated 2 classes, 2 courses (', $human);
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
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $this->assertEquals(2, count($decodedData[0]['runners']));
        $this->assertEquals(2, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['sex'] ?? null, $value->sex);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'] ?? '', $value->club?->short_name);
            $stage = $runnersJson[$key]['stage'];
            $this->assertEquals(UploadTypes::START_LIST, $stage['upload_type']);
            $this->assertEquals($stage['status_code'],
                $value->getResultList()[0]->status_code);
            $this->assertEquals($stage['start_time'],
                $value->getResultList()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2014-07-06T10:09:14.523+00:00', $stage['start_time']);
            }
            $this->assertEquals($stage['id'],
                $value->getResultList()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
        }
        $this->assertEquals('F', $runnersJson[0]['sex']);
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 1, 4, 4);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldAddEntryListsWihtouStartTimes()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => StartExamples::entriesImportWithoutStartTimes()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $jsonDecoded['meta']['human'][1] = '';
        $jsonDecoded['meta']['timings'] = [];
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
                'courses' => 2,
                'splits' => 0,
                'runnerResults' => 1,
            ],
            'humanColor' => '#FF0000',
            'human' => ['', ''],
            'timings' => []
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith('Updated (<b>Runner without runner_results</b>) 2 classes, 2 courses', $human);

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
            $this->assertEquals($runnersJson[$key]['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['sex'] ?? null, $value->sex);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $stage = $runnersJson[$key]['stage'];
            //$this->assertEquals(UploadTypes::START_LIST, $stage['upload_type']);
            //$this->assertEquals($stage['status_code'],
            //    $value->getResultList()[0]->status_code);
            //$this->assertEquals($stage['start_time'],
            //    $value->getResultList()[0]->start_time->jsonSerialize());
            //if ($key === 0) {
            //    $this->assertEquals('2014-07-06T10:09:14.523+00:00', $stage['start_time']);
            //}
            //$this->assertEquals($stage['id'],
            //    $value->getResultList()[0]->id);

            if (isset($value->getResultList()[0])) {
                $this->assertEquals(ResultType::STAGE,
                    $value->getResultList()[0]->result_type_id);
            } else {
                $this->assertEquals(ResultType::EMPTY, $stage['result_type_id']);
            }
        }
        $this->assertEquals('F', $runnersJson[0]['sex']);
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 1, 4, 1);
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
            ->orderByAsc('Classes.oe_key')
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
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(3, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['teams']));
        $this->assertEquals(2, count($decodedData[1]['teams'][0]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['teams'][0]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $currentRunner = $runnersJson[$key];
            $this->assertEquals($runnersJson[$key]['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($currentRunner['sicard'], $value->sicard);
            $this->assertEquals($currentRunner['id'], $value->id);
            $this->assertEquals($currentRunner['club']['short_name'], $value->club->short_name);
            $stage = $currentRunner['stage'];
            $this->assertEquals(UploadTypes::START_LIST, $stage['upload_type']);
            $this->assertEquals($stage['start_time'],
                $value->getResultList()[0]->start_time->jsonSerialize());
            $this->assertEquals('2024-11-10T09:30:00.000+00:00', $stage['start_time']);
            $this->assertEquals($stage['id'],
                $value->getResultList()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
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
            ->orderBy(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
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

    public function testAddNew_shouldAddIntermediatesWithRadiosAndDuplicatedBibs()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => IntermediateExamples::itermediateWithDuplicatedBibs()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);

        $jsonDecoded = $this->assertJsonResponseOK();

        $existingRunners = 2;
        $expectedNewRunners = 1;
        $this->assertEquals($existingRunners + $expectedNewRunners, RunnersTable::load()->find()->all()->count());

        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 3,
                'courses' => 1,
                'splits' => 6,
                'runnerResults' => 3,
            ],
            'humanColor' => '#FF0000',
        ];
        unset($jsonDecoded['meta']['human']);
        unset($jsonDecoded['meta']['timings']);
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringContainsString('Updated (<b>Duplicated runner Sara Alonso 1</b>) 1 classes, 1 courses', $human);

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->orderBy(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals(6, $dbSplits->count());
        /** @var Split $splitA */
        $splitA = $dbSplits->first();
        $this->assertEquals(true, $splitA->is_intermediate);
        $this->assertEquals(1, $splitA->order_number);
        $this->assertEquals(4, substr_count($splitA->class_id, '-'));
        $this->assertEquals(165, $splitA->control->station);
        /** @var Split $splitB */
        $splitB = $dbSplits->last();
        $this->assertEquals(true, $splitB->is_intermediate);
        $this->assertEquals(2, $splitB->order_number);
        $this->assertEquals(4, substr_count($splitA->class_id, '-'));
        $this->assertEquals(158, $splitB->control->station);
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
            '_c' => 'UploadedMeta',
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

    public function testAddNew_shouldAddFinishTimesTwice()
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
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', '10 Mas30F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']));
        $this->_assertRunnersWithFinishTimes($decodedData);
        $expectedControlAmount = $this->controlsAmount() + 2;
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());
        $runnerResultAmount = 3;
        $this->assertEquals($runnerResultAmount, RunnerResultsTable::load()->find()->all()->count());

        // second upload should not add again results
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData), json_encode($jsonDecoded));
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']), json_encode($decodedData));
        $this->_assertRunnersWithFinishTimes($decodedData, true);
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->orderBy(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals($expectedSplits, $dbSplits->count());
        /** @var Split $splitA */
        $splitA = $dbSplits->first();
        $this->assertEquals(false, $splitA->is_intermediate);
        $this->assertEquals(1, $splitA->order_number);
        $this->assertEquals(31, $splitA->control->station);
        $this->assertEquals($runnerResultAmount, RunnerResultsTable::load()->find()->all()->count());
    }

    public function testAddNew_shouldAddFinishTimesAsDNSAndLaterAsDNF()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $dns = ResultExamples::resultSimpleFinishTime();
        //unset($dns['event']['stages'][0]['classes'][0]['runners'][1]);
        $dns['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['status_code'] = StatusCode::DNS;
        $originalSplits = $dns['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['splits'];
        foreach ($dns['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['splits'] as $i => &$split) {
            $split['sicard'] = '';
            $split['status'] = Split::STATUS_MISSING;
            unset($split['reading_time']);
            unset($split['reading_milli']);
            unset($split['time_seconds']);
        }
        $data = ['oreplay_data_transfer' => $dns];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $expectedRunnerAmount = 2;
        $expectedSplits = 4;
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'] = [''];
        unset($jsonDecoded['meta']['timings']);
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => $expectedRunnerAmount,
                'courses' => 1,
                'splits' => $expectedSplits,
                'runnerResults' => 2,
            ],
            'humanColor' => '#075210',
            'human' => ['']
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith('Updated 1 classes, 1 courses (', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', '10 Mas30F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']));
        $this->_assertRunnersWithFinishTimes($decodedData, true, StatusCode::DNS);
        $expectedControlAmount = $this->controlsAmount() + 2;
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());
        $runnerResultAmount = 3;
        $this->assertEquals($runnerResultAmount, RunnerResultsTable::load()->find()->all()->count());

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->orderBy(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals(4, $dbSplits->count());

        // second upload should not add again results
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data['oreplay_data_transfer']['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['status_code']
            = StatusCode::DNF;
        $data['oreplay_data_transfer']['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['splits']
            = $originalSplits;
        $this->post($this->_getEndpoint() . '?version=300', $data);

        $jsonDecoded = $this->assertJsonResponseOK();
        $decodedData = $jsonDecoded['data'];
        $this->assertEquals($expectedRunnerAmount, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData), json_encode($jsonDecoded));
        $this->assertEquals($expectedRunnerAmount, count($decodedData[0]['runners']), json_encode($decodedData));
        $this->_assertRunnersWithFinishTimes($decodedData, true, StatusCode::DNF);
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());

        $dbSplits = SplitsTable::load()->find()
            ->where(['Splits.created >' => new FrozenTime('-1 minute')])
            ->contain(ControlsTable::name())
            ->orderBy(['Splits.order_number' => 'ASC', 'Splits.reading_time' => 'ASC'])
            ->all();
        $this->assertEquals(3, $dbSplits->count());
        /** @var Split $splitA */
        $splitA = $dbSplits->first();
        $this->assertEquals(false, $splitA->is_intermediate);
        $this->assertEquals(1, $splitA->order_number);
        $this->assertEquals(31, $splitA->control->station);
        $this->assertEquals($runnerResultAmount, RunnerResultsTable::load()->find()->all()->count());
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
            '_c' => 'UploadedMeta',
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
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(1, count($addedClasses));

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(0, count($res), 'Runner count in db');
    }

    private function _assertRunnersWithFinishTimes($decodedData, $skipSplits = false, $statusCode = '0')
    {
        $Table = RunnerResultsTable::load();
        $this->assertEquals(1, count($decodedData));
        $this->assertEquals(2, count($decodedData[0]['runners']));
        $firstRunner = $decodedData[0]['runners'][0];
        $this->assertEquals('Maria Ballesteros', $firstRunner['full_name']);
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
        $this->assertEquals($statusCode, $stage['status_code']);
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
        //$this->assertEquals(1, $stage['leg_number']);
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
        $this->assertEquals('Antonio Pino', $secondRunner['full_name']);
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
        //$this->assertEquals(1, $stage['leg_number']);
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
        $this->assertStringContainsString('Updated 2 classes, 2 courses (', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(3, count($addedClasses));
        $expectedClasses = ['ME', 'U-10', 'O ROJO F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(2, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            $this->assertEquals($runnersJson[$key]['stage']['start_time'],
                $value->getResultList()[0]->start_time->jsonSerialize());
            if ($key === 0) {
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['stage']['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['stage']['id'],
                $value->getResultList()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
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
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(3, count($addedClasses));
        $expectedClasses = ['ME', 'U-10', 'O ROJO F'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(2, count($res), 'Runner count in db');
        $this->assertEquals(1, count($decodedData[0]['runners']));
        $this->assertEquals(1, count($decodedData[1]['runners']));
        $runnersJson = array_merge($decodedData[0]['runners'], $decodedData[1]['runners']);
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($runnersJson[$key]['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($runnersJson[$key]['sicard'], $value->sicard);
            $this->assertEquals($runnersJson[$key]['bib_number'], $value->bib_number);
            $this->assertEquals($runnersJson[$key]['id'], $value->id);
            $this->assertEquals($runnersJson[$key]['club']['short_name'], $value->club->short_name);
            if ($key === 0) {
                $resultId = $runnersJson[$key]['stage']['id'];
                $this->assertEquals('2024-10-18T09:56:00.000+00:00', $runnersJson[$key]['stage']['start_time']);
            }
            $this->assertEquals($runnersJson[$key]['stage']['id'],
                $value->getResultList()[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
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

    public function testAddNew_shouldAddRelayResultsWithoutSamePersonRunningTwice()
    {
        Cache::clear();
        $RunnersTable = RunnersTable::load();
        $existingRunners = $RunnersTable->find()->all()->count();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => RelayExamples::twoTeamsWith2Runners4LegsEach()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 1 classes, 1 courses (', $jsonDecoded['meta']['human'][0]);
        $expected = [
            'classes' => 1,
            'courses' => 1,
            'runners' => 10,
            'splits' => 0,
            'runnerResults' => 16
        ];
        $this->assertEquals($expected, $jsonDecoded['meta']['updated']);
        $this->assertEquals(8, $RunnersTable->find()->all()->count() - $existingRunners);
    }

    public function testAddNew_shouldAddRelayResultsWithEvolution()
    {
        Cache::clear();
        $RunnersTable = RunnersTable::load();
        $existingRunners = $RunnersTable->find()->all()->count();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => RelayExamples::oneTeamLeg2()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 1 classes, 1 courses (', $jsonDecoded['meta']['human'][0]);
        $this->assertEquals(4, $RunnersTable->find()->all()->count() - $existingRunners);
        $expected = [
            'classes' => 1,
            'courses' => 1,
            'runners' => 5,
            'splits' => 0,
            'runnerResults' => 8
        ];
        $this->assertEquals($expected, $jsonDecoded['meta']['updated']);
        /** @var Team $team */
        $team = TeamsTable::load()->findTeamsInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)->first();
        $expectedTeamResult1 = [
            '_c' => TeamResult::C_NAME,
            'result_type_id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
            'start_time' => '2025-10-05T08:30:00.000+00:00',
            'finish_time' => null,
            'upload_type' => 'res_finish',
            'time_seconds' => (int) 0,
            'position' => (int) 0,
            'status_code' => '0',
            'is_nc' => false,
            'contributory' => null,
            'time_behind' => (int) 0,
            'time_neutralization' => (int) 0,
            'time_adjusted' => (int) 0,
            'time_penalty' => (int) 0,
            'time_bonus' => (int) 0,
            'points_final' => '0.0000',
            'points_adjusted' => '0.0000',
            'points_penalty' => '0.0000',
            'points_bonus' => '0.0000',
            'leg_number' => (int) 4,
            'note' => null,
            'splits' => []
        ];
        $stage = json_decode(json_encode($team->_getStage()), true);
        unset($stage['created']);
        $this->assertEqualsNoId($expectedTeamResult1, $stage);
        $results1 = TeamResultsTable::load()->find()->where(['team_id' => $team->id])->all();
        $this->assertEquals(4, $results1->count());

        // second
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => RelayExamples::oneTeamLeg4()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 1 classes, 1 courses (', $jsonDecoded['meta']['human'][0]);
        $this->assertEquals($expected, $jsonDecoded['meta']['updated']);
        $this->assertEquals(4, $RunnersTable->find()->all()->count() - $existingRunners);
        /** @var Team $team */
        $team = TeamsTable::load()->findTeamsInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)->first();
        $expectedTeamResult1 = [
            '_c' => TeamResult::C_NAME,
            'result_type_id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
            'start_time' => '2025-10-05T08:30:00.000+00:00',
            'finish_time' => '2025-10-05T09:41:17.000+00:00',
            'upload_type' => 'res_finish',
            'time_seconds' => (int) 4277,
            'position' => (int) 0,
            'status_code' => '0',
            'is_nc' => false,
            'contributory' => null,
            'time_behind' => (int) 0,
            'time_neutralization' => (int) 0,
            'time_adjusted' => (int) 0,
            'time_penalty' => (int) 0,
            'time_bonus' => (int) 0,
            'points_final' => '0.0000',
            'points_adjusted' => '0.0000',
            'points_penalty' => '0.0000',
            'points_bonus' => '0.0000',
            'leg_number' => (int) 4,
            'note' => null,
            'splits' => []
        ];
        $stage = json_decode(json_encode($team->_getStage()), true);
        unset($stage['created']);
        $this->assertEqualsNoId($expectedTeamResult1, $stage);
    }

    public function testAddNew_shouldAddRelayResultsWithoutSplitsTwice()
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
            'runnerResults' => 5,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Uploading results without splits</b>) 1 classes, 1 courses (', $human);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'SENIOR FEM'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            //$this->assertEquals($expectedClasses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
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
        $this->_assertNewOptionalTables(0, 1, 2, 0);
        $this->_assertNewBasicTables(1, 1, 1, 3, 3);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldAddTotalsWithPointsTwice()
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

        $newStage = StagesTable::load()->find()->orderByDesc('created')->firstOrFail();
        $this->assertEquals(StageType::TOTALS, $newStage->stage_type_id);
        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => $newStage->id])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $expectedClasses = ['F-E'];
        $this->assertEquals(count($expectedClasses), count($addedClasses));
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, $newStage->id)
            ->orderByAsc('last_name')
            ->all();
        $this->assertEquals(2, count($res), 'Runner count in db');

        $this->assertEquals(0, count($decodedData[0]['teams']));
        $this->_assertNewOptionalTables(0, 0, 0, 0);
        $this->_assertNewBasicTables(2, 1, 1, 2, 6);
        $this->_assertNewResultsTables(0, 0);
    }

    public function testAddNew_shouldAddTotalsIn2Stages()
    {
        Cache::clear();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => TotalsExamples::stage1RealTotalPoints()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 1,
            'runners' => 1,
            'courses' => 1,
            'splits' => 0,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Result type STAGE converted to PARTIAL_OVERALL</b>) 1 classes, 1 courses (0', $human);
        /** @var Stage $stage */
        $stage = StagesTable::load()->find()
            ->where(['stage_type_id' => StageType::TOTALS])->orderByDesc('created')->first();
        $this->assertEquals('', $stage->description);
        $runners = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, $stage->id)
            ->orderByDesc('first_name')->toArray();
        $results = json_decode(json_encode($runners), true);
        $this->assertEquals('Paco Fernandez', $results[0]['full_name']);
        $this->assertEquals(1000, $results[0]['overalls']['overall']['points_final']);
        $this->assertEquals(2053, $results[0]['overalls']['overall']['time_seconds']);
        $this->assertEquals(1, $results[0]['overalls']['overall']['position']);
        $this->assertEquals(1, count($results[0]['overalls']['parts']));
        $this->assertEquals(1000, $results[0]['overalls']['parts'][0]['points_final']);
        $this->assertEquals(2053, $results[0]['overalls']['parts'][0]['time_seconds']);
        $this->assertEquals(1, $results[0]['overalls']['parts'][0]['position']);
        $this->assertEquals(1, $results[0]['overalls']['parts'][0]['stage_order']);
        // runner 2
        //$this->assertEquals('Elmar Martinez', $results[1]['full_name']);
        //$this->assertEquals(960, $results[1]['overalls']['overall']['points_final']);
        //$this->assertEquals(2138, $results[1]['overalls']['overall']['time_seconds']);
        //$this->assertEquals(2, $results[1]['overalls']['overall']['position']);
        //$this->assertEquals(1, count($results[1]['overalls']['parts']));
        //$this->assertEquals(960, $results[1]['overalls']['parts'][0]['points_final']);
        //$this->assertEquals(2138, $results[1]['overalls']['parts'][0]['time_seconds']);
        //$this->assertEquals(2, $results[1]['overalls']['parts'][0]['position']);
        //$this->assertEquals(1, $results[1]['overalls']['parts'][0]['stage_order']);

        // upload second stage
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $dataTransfer = TotalsExamples::stage2RealTotalPoints();
        $data = ['oreplay_data_transfer' => $dataTransfer];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $jsonDecoded = $this->assertJsonResponseOK();
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'classes' => 1,
            'runners' => 1,
            'courses' => 1,
            'splits' => 0,
            'runnerResults' => 3,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertStringContainsString('Updated (<b>Result type STAGE converted to PARTIAL_OVERALL</b>) 1 classes, 1 courses (0', $human);
        /** @var Stage $stage */
        $stage = StagesTable::load()->find()
            ->where(['stage_type_id' => StageType::TOTALS])->orderByDesc('created')->first();
        $this->assertEquals('', $stage->description);
        $runners = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, $stage->id)
            ->orderByDesc('first_name')->toArray();
        $results = json_decode(json_encode($runners), true);
        $this->assertEquals('Paco Fernandez', $results[0]['full_name']);
        $this->assertEquals(2000, $results[0]['overalls']['overall']['points_final']);
        $this->assertEquals(2937, $results[0]['overalls']['overall']['time_seconds']);
        $this->assertEquals(1, $results[0]['overalls']['overall']['position']);
        $this->assertEquals(1, $results[0]['overalls']['overall']['stage_order']);
        $this->assertEquals(2, count($results[0]['overalls']['parts']));
        $this->assertEquals(1000, $results[0]['overalls']['parts'][0]['points_final']);
        $this->assertEquals(2053, $results[0]['overalls']['parts'][0]['time_seconds']);
        $this->assertEquals(1, $results[0]['overalls']['parts'][0]['position']);
        $this->assertEquals(1, $results[0]['overalls']['parts'][0]['stage_order']);
        $this->assertEquals(1000, $results[0]['overalls']['parts'][1]['points_final']);
        $this->assertEquals(884, $results[0]['overalls']['parts'][1]['time_seconds']);
        $this->assertEquals(1, $results[0]['overalls']['parts'][1]['position']);
        $this->assertEquals(2, $results[0]['overalls']['parts'][1]['stage_order']);
        $resultsPaco = RunnerResultsTable::load()->find()->where(['runner_id' => $results[0]['id']])->all();
        $this->assertEquals($expectedMeta['runnerResults'], count($resultsPaco), 'Amount of results for Paco');
    }

    public function testAddNew_shouldAddSplitsAndLaterIntermediatesWithRadios()
    {
        Cache::clear();
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        // 1st upload partial splits from a download
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $position = 154;
        $data = IntermediateExamples::intermediateResults();
        $s1time = '2024-01-16T09:56:47+00:00';
        $s2time = '2024-01-16T09:58:47+00:00';
        $data = $this->_prepare1stUploadPartialSplitsFromDownload($data, $position, $s1time, $s2time);
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $this->_assert1stUploadPartialSplitsFromDownload($position, $s1time, $s2time);

        // 2nd upload intermediates from radios
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => IntermediateExamples::intermediateResults()];
        $this->post($this->_getEndpoint() . '?version=' . UploadsController::NEW_VERSION, $data);
        $this->_assert2ndUploadPartialSplitsFromDownload($position, $s1time, $s2time);
    }

    private function _prepare1stUploadPartialSplitsFromDownload(array $data, int $position, string $s1time, string $s2time): array
    {
        $data['configuration'] = [
            'file' => '/path/tmp/SplitResults-edited.xml',
            'extension' => 'XML',
            'utf' => true,
            'known_data' => true,
            'contents' => 'ResultList',
            'results_type' => 'Breakdown',
            'one_stage' => true,
            'source' => 'OEv12',
            'iof_version' => '3.0'
        ];
        $data['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['position'] = $position;
        $data['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['splits'] = [
            (int)0 => [
                'sicard' => '8000001',
                'station' => '32',
                'points' => (int)0,
                'reading_time' => $s1time,
                'reading_milli' => (int)1705399007000,
                'time_seconds' => (int)1607,
                'bib_runner' => '1',
                'order_number' => (int)1
            ],
            (int)1 => [
                'sicard' => '8000001',
                'station' => '100',
                'points' => (int)0,
                'reading_time' => $s2time,
                'time_seconds' => (int)1727,
                'bib_runner' => '1',
                'order_number' => (int)2
            ]
        ];
        $data['event']['stages'][0]['classes'][0]['runners'][1]['runner_results'][0]['splits'] = [];
        return ['oreplay_data_transfer' => $data];
    }

    private function _assert1stUploadPartialSplitsFromDownload(int $position, string $s1time, string $s2time): void
    {
        $jsonDecoded = $this->assertJsonResponseOK();
        $human = $jsonDecoded['meta']['human'][0];
        $jsonDecoded['meta']['human'][0] = '';
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 2,
                'courses' => 1,
                'splits' => 2,
                'runnerResults' => 2,
            ],
            'humanColor' => '#075210',
            'human' => [''],
            'timings' => [],
        ];
        $jsonDecoded['meta']['timings'] = [];
        unset($jsonDecoded['meta']['human'][1]);
        $this->assertEquals($expectedMeta, $jsonDecoded['meta'], $human);
        $this->assertStringStartsWith('Updated 1 classes, 1 courses (', $human);
        $results = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(SplitsTable::name())
            ->orderByAsc('start_time')->all();
        $this->assertEquals(2, count($results));
        $this->assertEquals(UploadTypes::SPLITS, $results->first()->upload_type);
        $this->assertEquals($position, $results->first()->position);
        $splits = $results->first()->splits;
        $this->assertEquals(2, count($splits));
        $this->_assertSplit($splits[0], '32', false, $s1time);
        $this->_assertSplit($splits[1], '100', false, $s2time);
        $this->assertEquals(UploadTypes::SPLITS, $results->last()->upload_type);
        $this->assertEquals(0, $results->last()->position);
        $this->assertEquals([], $results->last()->splits);
    }

    private function _assert2ndUploadPartialSplitsFromDownload(int $position, string $s1time, string $s2time): void
    {
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
            'human' => [''],
            'timings' => [],
        ];
        $jsonDecoded['meta']['timings'] = [];
        unset($jsonDecoded['meta']['human'][1]);
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']);
        $this->assertStringStartsWith('Updated 1 classes, 1 courses (', $human);
        $results = RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(SplitsTable::name(), function (Query $q) {
                return $q->orderByAsc('reading_time')
                    ->orderByAsc('station')
                    ->orderByAsc('is_intermediate');
            })
            ->orderByAsc('start_time')->all();
        $this->assertEquals(2, count($results));
        $this->assertEquals(UploadTypes::SPLITS, $results->first()->upload_type);
        $this->assertEquals($position, $results->first()->position);
        $splits = $results->first()->splits;
        $this->assertEquals(4, count($splits));
        $this->_assertSplit($splits[0], '100', true, null);
        $this->_assertSplit($splits[1], '32', false, $s1time);
        $this->_assertSplit($splits[2], '32', true, $s1time);
        $this->_assertSplit($splits[3], '100', false, $s2time);
        $this->assertEquals(UploadTypes::SPLITS, $results->last()->upload_type);
        $this->assertEquals(0, $results->last()->position);
        $this->assertEquals(2, count($results->last()->splits));
    }

    private function _assertSplit(mixed $split, string $station, bool $isRadio, ?string $readingTime): void
    {
        $this->assertEquals('8000001', $split->sicard);
        $this->assertEquals($station, $split->station);
        $this->assertEquals($isRadio, $split->is_intermediate);
        if ($readingTime === null) {
            $this->assertEquals($readingTime, $split->reading_time);
        } else {
            $this->assertEquals($readingTime, $split->reading_time->toIso8601String());
        }
    }
}
