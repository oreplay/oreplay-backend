<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
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
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\UploadLogsTable;
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

class UploadsV2ControllerTest extends ApiCommonErrorsTest
{
    use UploadResponseTrait;

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

    protected function _getEndpointAddingToSwagger(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/v2/';
    }

    protected function _getEndpoint(): string
    {
        $this->skipNextRequestInSwagger();
        return $this->_getEndpointAddingToSwagger();
    }

    public function setUp(): void
    {
        $this->flushMemcached();
        parent::setUp();
    }

    private function _lockTheStage(): \Results\Lib\Import\StageUploadLock
    {
        $lock = new \Results\Lib\Import\StageUploadLock();
        $lock->acquire(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2);
        return $lock;
    }

    /**
     * A client that uploads every few seconds posts again while the previous file is still importing.
     * Two imports on one stage create the same runner twice, and the older one can finish last and write
     * its stale results over the newer ones, so the second is refused rather than queued: the client's
     * next file is seconds away and carries fresher data than anything a queue would hold.
     */
    public function testAddNew_shouldRefuseASecondUploadWhileTheStageIsStillImporting()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $lock = $this->_lockTheStage();

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadRejected(409);
        $this->assertEquals('error', $jsonDecoded['meta']['level']);
        $this->assertEquals([
            'level' => 'error',
            'code' => 'conflict',
            'text' => 'An upload for this stage is still being processed',
        ], $jsonDecoded['meta']['messages'][0]);
        $this->assertEquals(0, RunnerResultsTable::load()->find()
            ->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all()->count(),
            'a refused upload must not import anything');
        $lock->release();
    }

    public function testAddNew_shouldReleaseTheLockSoTheNextUploadGoesThrough()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];

        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk('first upload');
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
        // posting again replaces every header, so the token has to be set again
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), $data);

        $this->assertUploadOk('the stage must not stay locked after an upload finishes');
    }

    /**
     * The lock is released in a finally: a rejected payload that left it held would block the stage until
     * the entry expired, which is minutes of an event with no results.
     */
    public function testAddNew_shouldReleaseTheLockWhenTheUploadFails()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $broken = ResultExamples::resultSimpleFinishTime();
        $broken['event']['stages'][0]['classes'][0]['runners'][0]['runner_results'][0]['result_type'] = [];
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => $broken]);
        $this->assertNotEquals(200, $this->_response->getStatusCode());

        $lock = new \Results\Lib\Import\StageUploadLock();
        $this->assertTrue($lock->acquire(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2),
            'the stage is still locked after a failed upload');
        $lock->release();
    }

    public function testAddNew_onError()
    {
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadRejected(400);
        $expected = [
            'meta' => [
                'updated' => [
                    'classes' => 0,
                    'courses' => 0,
                    'runners' => 0,
                    'splits' => 0,
                    'runnerResults' => 0,
                ],
                'level' => 'error',
                'messages' => [[
                    'level' => 'error',
                    'code' => 'invalid_payload',
                    'text' => 'Invalid payload structure configuration.contents StartList | ResultList'
                        . ' and configuration.results_type Mixed',
                ]],
            ],
            'data' => []
        ];
        $this->assertUploadMeta($expected['meta'], $jsonDecoded);
        $this->assertEquals($expected['data'], $jsonDecoded['data']);
    }

    public function testAddNew_shouldRespondErrorWhenTheRawUploadIsNotFound()
    {
        Cache::clear();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = [
            'raw_upload_id' => '8fa0a698-5b49-433a-9339-e78ef216f12f',
            'stage_id' => StagesFixture::STAGE_FEDO_2,
        ];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadRejected(404);
        $this->assertEquals('error', $jsonDecoded['meta']['level']);
        $this->assertEquals(['classes' => 0, 'courses' => 0, 'runners' => 0, 'splits' => 0,
            'runnerResults' => 0], $jsonDecoded['meta']['updated']);
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
        $this->post($this->_getEndpoint());

        $jsonDecoded = $this->assertUploadRejected(400);
        $expected = [
            'meta' => [
                'updated' => [
                    'classes' => 0,
                    'courses' => 0,
                    'runners' => 0,
                    'splits' => 0,
                    'runnerResults' => 0,
                ],
                'level' => 'error',
                'messages' => [[
                    'level' => 'error',
                    'code' => 'invalid_payload',
                    'text' => 'Invalid payload structure configuration.contents StartList | ResultList'
                        . ' and configuration.results_type Mixed',
                ]],
            ],
            'data' => []
        ];
        $this->assertUploadMeta($expected['meta'], $jsonDecoded);
        $this->assertEquals($expected['data'], $jsonDecoded['data']);
    }

    public function testAddNew_shouldAddMixedContent()
    {
        $this->flushMemcached();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => MixedExamples::importMixed()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 6,
                'courses' => 2,
                'splits' => 3,
                'runnerResults' => 6,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
                'courses' => 2,
                'splits' => 0,
                'runnerResults' => 4,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        $expectedCourses = ['ME', 'WE/M20'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedCourses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $expectedRunners = [
            ['class' => 'ME', 'full_name' => 'Carlos Alonso', 'sicard' => '889818', 'bib_number' => '359',
                'sex' => 'F', 'club' => '', 'start_time' => '2014-07-06T10:09:14.523+00:00'],
            ['class' => 'ME', 'full_name' => 'Francisco Alvarez', 'sicard' => '', 'bib_number' => '255',
                'sex' => null, 'club' => 'BRIGHTNET', 'start_time' => '2014-07-06T13:11:00.000+00:00'],
            ['class' => 'WE', 'full_name' => 'Ana Gomez', 'sicard' => '7504274', 'bib_number' => '1348',
                'sex' => null, 'club' => 'Tullinge SK', 'start_time' => '2014-07-06T13:22:00.000+00:00'],
            ['class' => 'WE', 'full_name' => 'Maria Rodriguez', 'sicard' => '889312', 'bib_number' => '1512',
                'sex' => null, 'club' => 'Tullinge SK', 'start_time' => '2014-07-06T13:26:00.000+00:00'],
        ];
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $expectedRunner = $expectedRunners[$key];
            $this->assertEquals($expectedRunner['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($expectedRunner['sicard'], $value->sicard);
            $this->assertEquals($expectedRunner['bib_number'], $value->bib_number);
            $this->assertEquals($expectedRunner['sex'], $value->sex);
            $this->assertEquals($expectedRunner['club'], $value->club?->short_name ?? '');
            $this->assertEquals($expectedRunner['class'], $value->class->short_name);
            $result = $value->getResultList()[0];
            $this->assertEquals(UploadTypes::START_LIST, $result->upload_type);
            $this->assertEquals(StatusCode::OK, $result->status_code);
            $this->assertEquals($expectedRunner['start_time'], $result->start_time->jsonSerialize());
            $this->assertEquals(ResultType::STAGE, $result->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
                'courses' => 2,
                'splits' => 0,
                'runnerResults' => 1,
            ],
            'level' => 'warning',
            'messages' => [[
                'level' => 'warning',
                'code' => 'runner_without_results',
                'text' => 'Runner without runner_results',
            ]],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

        $addedClasses = $ClassesTable->find()
            ->where(['Classes.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->contain(CoursesTable::name())
            ->orderByAsc('Classes.oe_key')
            ->all();
        $this->assertEquals(2, count($addedClasses));
        $expectedClasses = ['ME', 'WE'];
        $expectedCourses = ['ME', 'WE/M20'];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($expectedCourses[$k], $class->course->short_name);
        }

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(4, count($res), 'Runner count in db');
        $expectedRunners = [
            ['class' => 'ME', 'full_name' => 'Carlos Alonso', 'sicard' => '889818', 'bib_number' => '359',
                'sex' => 'F', 'club' => 'BRIGHTNET'],
            ['class' => 'ME', 'full_name' => 'Francisco Alvarez', 'sicard' => '', 'bib_number' => '255',
                'sex' => null, 'club' => 'BRIGHTNET'],
            ['class' => 'WE', 'full_name' => 'Ana Gomez', 'sicard' => '7504274', 'bib_number' => '1348',
                'sex' => null, 'club' => 'Tullinge SK'],
            ['class' => 'WE', 'full_name' => 'Maria Rodriguez', 'sicard' => '889312', 'bib_number' => '1512',
                'sex' => null, 'club' => 'Tullinge SK'],
        ];
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $expectedRunner = $expectedRunners[$key];
            $this->assertEquals($expectedRunner['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($expectedRunner['sicard'], $value->sicard);
            $this->assertEquals($expectedRunner['bib_number'], $value->bib_number);
            $this->assertEquals($expectedRunner['sex'], $value->sex);
            $this->assertEquals($expectedRunner['club'], $value->club->short_name);
            $this->assertEquals($expectedRunner['class'], $value->class->short_name);

            if (isset($value->getResultList()[0])) {
                $this->assertEquals(ResultType::STAGE,
                    $value->getResultList()[0]->result_type_id);
            } else {
                $this->assertEquals(ResultType::EMPTY, $value->_getStage()->result_type_id);
            }
        }
        $this->_assertNewOptionalTables(0, 0, 0);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'updated' => [
                'classes' => 2,
                'runners' => 4,
                'courses' => 1,
                'splits' => 0,
                'runnerResults' => 4,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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
        $sharedCourse = 'Full Score';
        $courseIds = [];
        foreach ($addedClasses as $k => $class) {
            $this->assertEquals($expectedClasses[$k], $class->short_name);
            $this->assertEquals($sharedCourse, $class->course->short_name);
            $courseIds[] = $class->course->id;
        }
        $this->assertEquals(1, count(array_unique($courseIds)), 'both classes share the same course row');

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->where(['Runners.created >' => new FrozenTime('-1 minute')])
            ->orderByAsc('last_name')
            ->all();

        $this->assertEquals(3, count($res), 'Runner count in db');
        $expectedRunners = [
            // runners of a team are attached to the team, not directly to the class
            ['class' => 'Individual', 'full_name' => 'Jorge Alonsolo', 'sicard' => '8530222',
                'club' => 'Albacete BMT CASAS DE VES'],
            ['class' => null, 'full_name' => 'Paco Morenoa', 'sicard' => '8008999',
                'club' => 'Alicante SANT_JOAN'],
            ['class' => null, 'full_name' => 'Andrea Ponceb', 'sicard' => '1398555',
                'club' => 'Alicante SANT_JOAN'],
        ];
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $expectedRunner = $expectedRunners[$key];
            $this->assertEquals($expectedRunner['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($expectedRunner['sicard'], $value->sicard);
            $this->assertEquals($expectedRunner['club'], $value->club->short_name);
            $this->assertEquals($expectedRunner['class'], $value->class?->short_name);
            $result = $value->getResultList()[0];
            $this->assertEquals(UploadTypes::START_LIST, $result->upload_type);
            $this->assertEquals('2024-11-10T09:30:00.000+00:00', $result->start_time->jsonSerialize());
            $this->assertEquals(ResultType::STAGE, $result->result_type_id);
        }
        $this->_assertNewOptionalTables(1, 1, 0);
        $this->_assertNewBasicTables(2, 1, 2, 3, 3);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 2,
                'courses' => 1,
                'splits' => 4,
                'runnerResults' => 2,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        unset($jsonDecoded['meta']['timings']);
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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

    /**
     * @return string[] one entry per stored split, as station@order_number
     */
    private function _punchesInUploadedStage(): array
    {
        $punches = [];
        $rows = SplitsTable::load()->find()
            ->where([SplitsTable::field('stage_id') => StagesFixture::STAGE_FEDO_2])
            ->contain(ControlsTable::name())
            ->all();
        /** @var Split $row */
        foreach ($rows as $row) {
            $punches[] = ($row->control->station ?? '?') . '@' . $row->order_number;
        }
        sort($punches);
        return $punches;
    }

    private function _withExtraPunchAtStation55(array $payload): array
    {
        $classes =& $payload['event']['stages'][0]['classes'];
        foreach ($classes as $classIndex => $class) {
            foreach ($class['runners'] as $runnerIndex => $runner) {
                foreach ($runner['runner_results'] as $resultIndex => $result) {
                    $extraPunch = $result['splits'][0];
                    $extraPunch['station'] = '55';
                    $extraPunch['order_number'] = 3;
                    $classes[$classIndex]['runners'][$runnerIndex]['runner_results'][$resultIndex]['splits'][]
                        = $extraPunch;
                }
            }
        }
        unset($classes);
        return $payload;
    }

    /**
     * A radio upload resends every punch the runner has made so far, not only the new one, and it is
     * the one upload type that does not replace the stored splits — deleting them would take the
     * downloaded chip readings with it. Without a narrower replacement each batch stored the whole
     * history again.
     */
    public function testAddNew_shouldNotDuplicateEarlierPunchesOnASecondRadioBatch()
    {
        Cache::clear();
        ClassesTable::load()->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);
        $firstBatch = IntermediateExamples::intermediateResults();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => $firstBatch]);
        $this->assertUploadOk();
        $this->assertEquals(['100@2', '100@2', '32@1', '32@1'], $this->_punchesInUploadedStage());

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(),
            ['oreplay_data_transfer' => $this->_withExtraPunchAtStation55($firstBatch)]);
        $this->assertUploadOk();

        $this->assertEquals(
            ['100@2', '100@2', '32@1', '32@1', '55@3', '55@3'],
            $this->_punchesInUploadedStage(),
            'the two runners keep one punch per station, not a second copy of the earlier ones'
        );
    }

    public function testAddNew_shouldReprocessIntermediatesWhenReprocessAllIsRequested()
    {
        Cache::clear();
        ClassesTable::load()->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);
        $batch = IntermediateExamples::intermediateResults();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => $batch]);
        $this->assertUploadOk();
        $storedSplitIds = $this->_splitIdsInUploadedStage();

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint() . '?reprocess_all=1', ['oreplay_data_transfer' => $batch]);
        $this->assertUploadOk();

        $this->assertEquals([], array_intersect($storedSplitIds, $this->_splitIdsInUploadedStage()),
            'reprocess_all rewrites the stored punches instead of skipping them on an equal hash');
        $this->assertEquals(['100@2', '100@2', '32@1', '32@1'], $this->_punchesInUploadedStage(),
            'they are rewritten once, not appended to the ones already stored');
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();

        $existingRunners = 2;
        $expectedNewRunners = 1;
        $this->assertEquals($existingRunners + $expectedNewRunners, RunnersTable::load()->find()->all()->count());

        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 3,
                'courses' => 1,
                'splits' => 6,
                'runnerResults' => 3,
            ],
            'level' => 'error',
            'messages' => [[
                'level' => 'error',
                'code' => 'duplicated_runner',
                'text' => 'Duplicated runner Sara Alonso 1',
                'context' => ['class' => 'INF FEM', 'bib' => '1'],
            ]],
        ];
        unset($jsonDecoded['meta']['timings']);
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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
        $this->post($this->_getEndpoint(), $data);

        // v2 answers a real error status; v1 keeps 202 for its documented contract
        $this->assertEquals(403, $this->_response->getStatusCode(), $this->_getBodyAsString());
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'courses' => 0,
                'runners' => 0,
                'splits' => 0,
                'runnerResults' => 0,
            ],
            'level' => 'error',
            'messages' => [[
                'level' => 'error',
                'code' => 'forbidden',
                'text' => 'Invalid Bearer token',
            ]],
        ];
        $this->assertUploadMeta($expectedMeta, json_decode((string)$this->_getBodyAsString(), true));
    }

    public function testAddNew_shouldAddFinishTimesTwice()
    {
        $this->flushMemcached();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $ClassesTable = ClassesTable::load();
        $ClassesTable->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedRunnerAmount = 2;
        $expectedSplits = 3;
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => $expectedRunnerAmount,
                'courses' => 1,
                'splits' => $expectedSplits,
                'runnerResults' => $expectedRunnerAmount,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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
        $this->_assertRunnersWithFinishTimes();
        $expectedControlAmount = $this->controlsAmount() + 2;
        $this->assertEquals($expectedControlAmount, ControlsTable::load()->find()->all()->count());
        $runnerResultAmount = 3;
        $this->assertEquals($runnerResultAmount, RunnerResultsTable::load()->find()->all()->count());

        // second upload should not add again results
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], json_encode($jsonDecoded));
        $this->_assertRunnersWithFinishTimes();
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

    public function testAddNew_shouldReprocessAnUnchangedClassWhenReprocessAllIsRequested()
    {
        $this->flushMemcached();
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        ClassesTable::load()->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint(), $data);
        $firstUpload = $this->assertUploadOk()['meta']['updated'];

        $expectedDatabase = [
            'runners' => RunnersTable::load()->find()->all()->count(),
            'runnerResults' => RunnerResultsTable::load()->find()->all()->count(),
            'splits' => SplitsTable::load()->find()->all()->count(),
            'controls' => ControlsTable::load()->find()->all()->count(),
        ];

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), $data);
        $skipped = $this->assertUploadOk()['meta']['updated'];
        $this->assertEquals(0, $skipped['classes'], 'an unchanged class is skipped without force');

        $storedSplitIds = $this->_splitIdsInUploadedStage();

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint() . '?reprocess_all=1', $data);
        $reprocessed = $this->assertUploadOk()['meta']['updated'];
        $this->assertEquals(1, $reprocessed['classes'], 'reprocess_all ignores the hash of an unchanged class');
        $this->assertEquals(1, $reprocessed['courses']);
        $this->assertEquals(2, $reprocessed['runners']);
        $this->assertEquals($firstUpload['splits'], $reprocessed['splits'],
            'reprocess_all ignores the hash of unchanged results and writes every split again');

        $rewrittenSplitIds = $this->_splitIdsInUploadedStage();
        $this->assertEquals([], array_intersect($storedSplitIds, $rewrittenSplitIds),
            'the stored splits are replaced, not kept');

        $this->assertEquals($expectedDatabase, [
            'runners' => RunnersTable::load()->find()->all()->count(),
            'runnerResults' => RunnerResultsTable::load()->find()->all()->count(),
            'splits' => SplitsTable::load()->find()->all()->count(),
            'controls' => ControlsTable::load()->find()->all()->count(),
        ], 'forcing reprocesses without duplicating anything');
    }

    public function testAddNew_shouldStoreTheControlSetOfAnUnorderedStage()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        StagesTable::load()->updateAll(
            ['stage_type_id' => StageType::RAID],
            ['id' => StagesFixture::STAGE_FEDO_2]);
        ClassesTable::load()->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $course = CoursesTable::load()->find()
            ->where(['Courses.stage_id' => StagesFixture::STAGE_FEDO_2])->firstOrFail();
        $this->assertFalse((bool)$course->is_ordered, 'a raid course has no order between its controls');
        $this->assertEquals(['31', '33'], $this->_courseControlStations(StagesFixture::STAGE_FEDO_2),
            'the control set is stored so the class does not need the punch-derived fallback');
    }

    private function _courseControlStations(string $stageId): array
    {
        return CourseControlsTable::load()->find()
            ->where(['CourseControls.stage_id' => $stageId])
            ->orderByAsc('CourseControls.order_number')
            ->all()->extract('station')->toList();
    }

    public function testAddNew_shouldReplaceTeamSplitsInsteadOfAccumulatingThem()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => MixedExamples::teamResultWithSplitsAtStations([31, 32])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $this->assertEquals([31, 32], $this->_teamSplitStations());

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => MixedExamples::teamResultWithSplitsAtStations([31, 33])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $this->assertEquals([31, 33], $this->_teamSplitStations(),
            'the stored team splits are replaced, the old station 32 must be gone');
    }

    private function _teamSplitStations(): array
    {
        $stations = SplitsTable::load()->find()
            ->where([
                'Splits.team_result_id IS NOT' => null,
                'Splits.stage_id' => StagesFixture::STAGE_FEDO_2,
            ])
            ->orderByAsc('Splits.order_number')
            ->all()->extract('station')->toList();
        return array_map('intval', $stations);
    }

    private function _splitIdsInUploadedStage(): array
    {
        return SplitsTable::load()->find()
            ->where(['Splits.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->all()->extract('id')->toList();
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
            $split['status'] = Split::STATUS_MISSING;
            unset($split['reading_time']);
            unset($split['reading_milli']);
            unset($split['time_seconds']);
        }
        $data = ['oreplay_data_transfer' => $dns];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedRunnerAmount = 2;
        $expectedSplits = 4;
        unset($jsonDecoded['meta']['timings']);
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => $expectedRunnerAmount,
                'courses' => 1,
                'splits' => $expectedSplits,
                'runnerResults' => 2,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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
        $this->_assertRunnersWithFinishTimes(false, StatusCode::DNS);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals([], $jsonDecoded['data'], json_encode($jsonDecoded));
        $this->_assertRunnersWithFinishTimes(true, StatusCode::DNF);
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
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertUploadRejected(400);
        $now = new FrozenTime();
        $expectedMeta = [
            'updated' => [
                'classes' => 0,
                'courses' => 0,
                'runners' => 0,
                'splits' => 0,
                'runnerResults' => 0,
            ],
            'level' => 'error',
            'messages' => [[
                'level' => 'error',
                'code' => 'invalid_payload',
                'text' => 'Cannot add start times when there are already finish times',
            ]],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);

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

    private function _assertRunnersWithFinishTimes(bool $hasDownloadedSplits = true, string $statusCode = '0')
    {
        $Table = RunnerResultsTable::load();
        $runners = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderByAsc('last_name')
            ->all()
            ->toArray();
        $this->assertEquals(2, count($runners));
        /** @var Runner $firstRunner */
        $firstRunner = $runners[0];
        $this->assertEquals('10 Mas30F', $firstRunner->class->short_name);
        $this->assertEquals('Maria Ballesteros', $firstRunner->first_name . ' ' . $firstRunner->last_name);
        $this->assertEquals('125', $firstRunner->bib_number);
        $this->assertEquals('4440522', $firstRunner->sicard);
        $this->assertEquals('Independiente', $firstRunner->club->short_name);
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $firstRunner->id])->all()->count());
        $stage = $firstRunner->_getStage();
        $this->assertEquals(ResultType::STAGE, $stage->result_type_id);
        $this->assertEquals(1, $stage->position);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $stage->start_time->jsonSerialize());
        $this->assertEquals('2024-09-29T12:26:54.000+00:00', $stage->finish_time->jsonSerialize());
        $this->assertEquals(5214, $stage->time_seconds);
        $this->assertEquals($statusCode, $stage->status_code);
        $this->assertEquals(UploadTypes::FINISH_TIMES, $stage->upload_type);
        $this->_assertResultWithoutAdjustments($stage, 0);
        if ($hasDownloadedSplits) {
            $splits = $this->_findSplitsOfResult($stage->id);
            $this->assertEquals(2, count($splits));
            $this->assertEquals(31, $splits[0]->control->station);
            $this->assertEquals(1, $splits[0]->order_number);
            $this->assertEquals('2024-01-28T10:15:05.000+00:00', $splits[0]->reading_time->jsonSerialize());
            $this->assertEquals(33, $splits[1]->control->station);
            $this->assertEquals(2, $splits[1]->order_number);
            $this->assertEquals('2024-01-28T10:18:37.000+00:00', $splits[1]->reading_time->jsonSerialize());
        }
        /** @var Runner $secondRunner */
        $secondRunner = $runners[1];
        $this->assertEquals('10 Mas30F', $secondRunner->class->short_name);
        $this->assertEquals('Antonio Pino', $secondRunner->first_name . ' ' . $secondRunner->last_name);
        $this->assertEquals('105', $secondRunner->bib_number);
        $this->assertEquals('4540555', $secondRunner->sicard);
        $this->assertEquals('Independiente', $secondRunner->club->short_name);
        $this->assertEquals(1, $Table->find()->where(['runner_id' => $secondRunner->id])->all()->count());
        $stage = $secondRunner->_getStage();
        $this->assertEquals(ResultType::STAGE, $stage->result_type_id);
        $this->assertEquals(2, $stage->position);
        $this->assertEquals('2024-09-29T11:00:00.000+00:00', $stage->start_time->jsonSerialize());
        $this->assertEquals('2024-09-29T11:48:49.000+00:00', $stage->finish_time->jsonSerialize());
        $this->assertEquals(UploadTypes::FINISH_TIMES, $stage->upload_type);
        $this->assertEquals(StatusCode::OK, $stage->status_code);
        $this->_assertResultWithoutAdjustments($stage, 44);
        if ($hasDownloadedSplits) {
            $this->assertEquals(1, count($this->_findSplitsOfResult($stage->id)));
        }
    }

    private function _assertResultWithoutAdjustments(RunnerResult $result, int $timeBehind): void
    {
        $this->assertEquals($timeBehind, $result->time_behind);
        $this->assertEquals(0, $result->time_neutralization);
        $this->assertEquals(0, $result->time_adjusted);
        $this->assertEquals(0, $result->time_penalty);
        $this->assertEquals(0, $result->time_bonus);
        $this->assertEquals(0, $result->points_final);
        $this->assertEquals(0, $result->points_adjusted);
        $this->assertEquals(0, $result->points_penalty);
        $this->assertEquals(0, $result->points_bonus);
    }

    /**
     * @return Split[]
     */
    private function _findSplitsOfResult(string $runnerResultId): array
    {
        return SplitsTable::load()->find()
            ->where(['Splits.runner_result_id' => $runnerResultId])
            ->contain(ControlsTable::name())
            ->orderByAsc('Splits.order_number')
            ->all()
            ->toArray();
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertStartsTimesFrom2Classes($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => ResultExamples::resultImport2CategoriesSplits()];
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertSplitsTimesFrom2Classes($jsonDecoded);
    }

    private function _assertStartsTimesFrom2Classes($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'classes' => 2,
            'runners' => 2,
            'courses' => 2,
            'splits' => 0,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);

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
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $expectedRunner = $this->_expectedRunnersOf2Classes()[$key];
            $this->assertEquals($expectedRunner['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($expectedRunner['sicard'], $value->sicard);
            $this->assertEquals($expectedRunner['bib_number'], $value->bib_number);
            $this->assertEquals($expectedRunner['club'], $value->club->short_name);
            $this->assertEquals($expectedRunner['class'], $value->class->short_name);
            $this->assertEquals('2024-10-18T09:56:00.000+00:00',
                $value->getResultList()[0]->start_time->jsonSerialize());
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 2, 2, 2);
        $this->_assertNewResultsTables(0, 0);
    }

    private function _expectedRunnersOf2Classes(): array
    {
        return [
            ['class' => 'U-10', 'full_name' => 'Maria Alvarez', 'sicard' => '8502455',
                'bib_number' => '3874', 'club' => 'Valencia VERD3'],
            ['class' => 'O ROJO F', 'full_name' => 'Ana Rodriguez', 'sicard' => '2063133',
                'bib_number' => '1329', 'club' => 'Sevilla MONTELLANO'],
        ];
    }

    private function _assertSplitsTimesFrom2Classes($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();

        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'classes' => 2,
            'runners' => 2,
            'courses' => 2,
            'splits' => 2,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);

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
        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $expectedRunner = $this->_expectedRunnersOf2Classes()[$key];
            $this->assertEquals($expectedRunner['full_name'], $value->first_name . ' ' . $value->last_name);
            $this->assertEquals($expectedRunner['sicard'], $value->sicard);
            $this->assertEquals($expectedRunner['bib_number'], $value->bib_number);
            $this->assertEquals($expectedRunner['club'], $value->club->short_name);
            $this->assertEquals($expectedRunner['class'], $value->class->short_name);
            if ($key === 0) {
                $resultId = $value->getResultList()[0]->id;
                $this->assertEquals('2024-10-18T09:56:00.000+00:00',
                    $value->getResultList()[0]->start_time->jsonSerialize());
            }
            $this->assertEquals(ResultType::STAGE,
                $value->getResultList()[0]->result_type_id);
        }
        $this->_assertNewOptionalTables(0, 0, 0);
        $this->_assertNewBasicTables(2, 2, 2, 2, 2);
        $this->_assertNewResultsTables(2, 1);
        /** @var RunnerResult $res */
        $res = RunnerResultsTable::load()->get($resultId);
        $this->assertEquals('"2024-10-18T09:56:00.000+00:00"', json_encode($res->start_time));
        $this->assertEquals('"2024-10-18T10:09:40.000+00:00"', json_encode($res->finish_time));
        $this->assertEquals(0, $res->time_behind);
        $this->assertEquals(820, $res->time_seconds);
        // changes whenever UploadHelper::md5Encode() changes shape, as it did when it began
        // canonicalising so XML and JSON of one class hash alike
        $this->assertEquals('c1c712406348d53ff0529d351917f2c5', $res->upload_hash);
    }

    private function _assertNewOptionalTables($teams, $teamsResults, $answers): void
    {
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals('Uploading results without splits', $jsonDecoded['meta']['messages'][0]['text']);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals('Uploading results without splits', $jsonDecoded['meta']['messages'][0]['text']);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->assertEquals('Uploading results without splits', $jsonDecoded['meta']['messages'][0]['text']);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertSimple3relay($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $dataTransfer = RelayExamples::simple3relay();
        $dataTransfer['event']['stages'][0]['classes'][0]['teams'][0]['team_results'][0]['time_seconds'] = 3601;
        $data = ['oreplay_data_transfer' => $dataTransfer];
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertSimple3relay($jsonDecoded);
    }

    private function _assertSimple3relay($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'classes' => 1,
            'runners' => 4,
            'courses' => 0,
            'splits' => 0,
            'runnerResults' => 5,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertEquals('Uploading results without splits', $jsonDecoded['meta']['messages'][0]['text']);

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
        /** @var Team $dbTeam */
        $dbTeam = TeamsTable::load()
            ->findTeamsInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->firstOrFail();
        $this->assertEquals(3, count($dbTeam->runners));
        $this->assertEquals([1, 2, 3], array_map(fn($runner) => $runner->leg_number, $dbTeam->runners));
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
        $this->_assertNewOptionalTables(1, 2, 0);
        $this->_assertNewBasicTables(1, 0, 1, 3, 3);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertTotals($jsonDecoded);

        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $dataTransfer = TotalsExamples::simpleTotalPoints(2932);
        $data = ['oreplay_data_transfer' => $dataTransfer];
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $this->_assertTotals($jsonDecoded);
    }

    private function _assertTotals($jsonDecoded)
    {
        $ClassesTable = ClassesTable::load();
        $this->assertEquals([], $jsonDecoded['data'], 'V2 never returns the saved entity graph');
        $expectedMeta = [
            'classes' => 1,
            'runners' => 2,
            'courses' => 0,
            'splits' => 0,
            'runnerResults' => 6,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertEquals('Result type STAGE converted to PARTIAL_OVERALL', $jsonDecoded['meta']['messages'][0]['text']);

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

        $this->_assertNewOptionalTables(0, 0, 0);
        $this->_assertNewBasicTables(2, 0, 1, 2, 6);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $expectedMeta = [
            'classes' => 1,
            'runners' => 1,
            'courses' => 0,
            'splits' => 0,
            'runnerResults' => 2,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertEquals('Result type STAGE converted to PARTIAL_OVERALL', $jsonDecoded['meta']['messages'][0]['text']);
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
        $this->post($this->_getEndpoint(), $data);
        $jsonDecoded = $this->assertUploadOk();
        $expectedMeta = [
            'classes' => 1,
            'runners' => 1,
            'courses' => 0,
            'splits' => 0,
            'runnerResults' => 3,
        ];
        $this->assertEquals($expectedMeta, $jsonDecoded['meta']['updated']);
        $this->assertEquals('Result type STAGE converted to PARTIAL_OVERALL', $jsonDecoded['meta']['messages'][0]['text']);
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
        $this->post($this->_getEndpoint(), $data);
        $this->_assert1stUploadPartialSplitsFromDownload($position, $s1time, $s2time);

        // 2nd upload intermediates from radios
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $data = ['oreplay_data_transfer' => IntermediateExamples::intermediateResults()];
        $this->post($this->_getEndpoint(), $data);
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
        $jsonDecoded = $this->assertUploadOk();
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 2,
                'courses' => 1,
                'splits' => 2,
                'runnerResults' => 2,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);
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
        $jsonDecoded = $this->assertUploadOk();
        $expectedMeta = [
            'updated' => [
                'classes' => 1,
                'runners' => 2,
                'courses' => 1,
                'splits' => 4,
                'runnerResults' => 2,
            ],
            'level' => 'info',
            'messages' => [],
        ];
        $this->assertUploadMeta($expectedMeta, $jsonDecoded);
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
        $this->assertEquals($station, $split->station);
        $this->assertEquals($isRadio, $split->is_intermediate);
        if ($readingTime === null) {
            $this->assertEquals($readingTime, $split->reading_time);
        } else {
            $this->assertEquals($readingTime, $split->reading_time->toIso8601String());
        }
    }

    public function testAddNew_aRejectedUploadAnswersAnErrorStatus()
    {
        // no event token. v2 is not bound by v1's 202-for-every-failure contract, so a client can
        // tell a rejected upload from an accepted one without reading meta.human
        $data = ['oreplay_data_transfer' => IntermediateExamples::intermediateResults()];
        $this->post($this->_getEndpoint(), $data);

        $this->assertUploadRejected(403);
    }

    public function testAddNew_shouldUnionTheControlsOfAnUnorderedStageIgnoringPunchOrder()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        StagesTable::load()->updateAll(
            ['stage_type_id' => StageType::RAID],
            ['id' => StagesFixture::STAGE_FEDO_2]);

        // two runners of the same class visiting different controls, in orders that disagree
        $data = ['oreplay_data_transfer' => $this->_raidUpload([
            ['20', '10', '15'],
            ['15', '30'],
        ])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $this->assertEquals(['10', '15', '20', '30'], $this->_courseControlStations(StagesFixture::STAGE_FEDO_2),
            'every control anyone visited, ascending — not the sequence either runner punched');
    }

    private function _raidUpload(array $stationsPerRunner): array
    {
        $runners = [];
        foreach ($stationsPerRunner as $i => $stations) {
            $splits = [];
            foreach ($stations as $order => $station) {
                $splits[] = [
                    'id' => '',
                    'station' => $station,
                    'order_number' => $order + 1,
                    'reading_time' => '2026-01-01T10:0' . $order . ':00.000+00:00',
                ];
            }
            $runners[] = [
                'id' => '',
                'uuid' => '',
                'sicard' => (string)(9000001 + $i),
                'first_name' => 'Raid' . $i,
                'last_name' => 'Runner' . $i,
                'bib_number' => (string)(500 + $i),
                'is_nc' => false,
                'runner_results' => [[
                    'id' => '',
                    'start_time' => '2026-01-01T10:00:00.000+00:00',
                    'finish_time' => '2026-01-01T11:00:00.000+00:00',
                    'time_seconds' => 3600,
                    'position' => $i + 1,
                    'status_code' => StatusCode::OK,
                    'leg_number' => 1,
                    'splits' => $splits,
                    'result_type' => ['id' => ResultType::STAGE, 'description' => 'Stage'],
                ]],
            ];
        }
        return [
            'configuration' => [
                'source_vendor' => 'oreplay',
                'source' => 'IofXml',
                'source_version' => '3.0',
                'contents' => 'ResultList',
                'results_type' => 'Breakdown',
                'utf' => true,
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'raid',
                'stages' => [[
                    'id' => StagesFixture::STAGE_FEDO_2,
                    'order_number' => 1,
                    'description' => 'raid',
                    'classes' => [[
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '1',
                        'short_name' => 'RAID',
                        'long_name' => 'Raid class',
                        'course' => [
                            'id' => '',
                            'uuid' => '',
                            'oe_key' => '77',
                            'short_name' => 'R1',
                            'distance' => '9000',
                            'climb' => '',
                            'controls' => 4,
                        ],
                        'runners' => $runners,
                    ]],
                ]],
            ],
        ];
    }

    public function testAddNew_shouldKeepARadioThatTheCourseVisitsTwice()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        // an ordinary ordered stage: the control before the finish is passed on every relay leg
        $data = ['oreplay_data_transfer' => $this->_raidUpload([
            ['31', '100', '32', '100'],
            ['31', '100', '32', '100'],
        ])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();
        ControlsTable::load()->markIntermediateStations(StagesFixture::STAGE_FEDO_2, ['100']);

        $this->assertEquals(['31', '100', '32', '100'],
            $this->_courseControlStations(StagesFixture::STAGE_FEDO_2),
            'the course keeps both visits, order_number tells them apart');

        $radios = [];
        foreach (ClassesTable::load()->getByStageWithRadios(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2) as $c) {
            foreach ($c->splits as $radio) {
                $radios[] = (string)$radio->station;
            }
        }
        $this->assertEquals(['100', '100'], $radios,
            'a radio passed twice is listed twice, so the live splits table stays aligned');
    }

    public function testAddNew_shouldStoreTheCourseOfARelayClass()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        StagesTable::load()->updateAll(
            ['stage_type_id' => StageType::RELAY],
            ['id' => StagesFixture::STAGE_FEDO_2]);

        // the three forking variants of upload-courses.md 8.2, run in a different leg order per team
        $data = ['oreplay_data_transfer' => $this->_relayUpload([
            [['32', '60', '100'], ['60', '50', '100'], ['54', '60', '50', '100']],
            [['54', '60', '50', '100'], ['32', '60', '100'], ['60', '50', '100']],
        ])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $stations = $this->_courseControlStations(StagesFixture::STAGE_FEDO_2);
        $this->assertNotEmpty($stations,
            'a relay keeps its runners inside teams, and the course importer has to reach them');
        $this->assertEquals(['60', '100'], $stations,
            'the controls every leg passed, in the order all three variants agree on');
    }

    public function testAddNew_shouldKeepRepeatedControlsInTheCommonCourseOfARelay()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        StagesTable::load()->updateAll(
            ['stage_type_id' => StageType::RELAY],
            ['id' => StagesFixture::STAGE_FEDO_2]);

        // 100 is the arena control, passed twice on every variant
        $data = ['oreplay_data_transfer' => $this->_relayUpload([
            [['32', '100', '60', '100'], ['50', '100', '60', '100'], ['54', '100', '60', '50', '100']],
            [['54', '100', '60', '50', '100'], ['32', '100', '60', '100'], ['50', '100', '60', '100']],
        ])];
        $this->post($this->_getEndpoint(), $data);
        $this->assertUploadOk();

        $this->assertEquals(['100', '60', '100'],
            $this->_courseControlStations(StagesFixture::STAGE_FEDO_2),
            'the arena control is common twice, so it is two columns and not one');
    }

    private function _relayUpload(array $legsPerTeam, array $variants = []): array
    {
        $teams = [];
        foreach ($legsPerTeam as $t => $legs) {
            $runners = [];
            foreach ($legs as $leg => $stations) {
                $splits = [];
                foreach ($stations as $order => $station) {
                    $splits[] = [
                        'id' => '',
                        'station' => $station,
                        'order_number' => $order + 1,
                        'reading_time' => '2026-01-01T10:0' . $order . ':00.000+00:00',
                    ];
                }
                $runner = [
                    'id' => '',
                    'uuid' => '',
                    'sicard' => (string)(7000000 + $t * 10 + $leg),
                    'first_name' => 'Team' . $t,
                    'last_name' => 'Leg' . ($leg + 1),
                    'bib_number' => (string)(100 + $t) . '-' . ($leg + 1),
                    'leg_number' => $leg + 1,
                    'is_nc' => false,
                    'runner_results' => [[
                        'id' => '',
                        'start_time' => '2026-01-01T10:00:00.000+00:00',
                        'finish_time' => '2026-01-01T11:00:00.000+00:00',
                        'time_seconds' => 3600,
                        'position' => $t + 1,
                        'status_code' => StatusCode::OK,
                        'leg_number' => $leg + 1,
                        'splits' => $splits,
                        'result_type' => ['id' => ResultType::STAGE, 'description' => 'Stage'],
                    ]],
                ];
                if ($variants[$t][$leg] ?? null) {
                    $runner['course'] = [
                        'id' => '', 'uuid' => '', 'oe_key' => '9004',
                        'short_name' => $variants[$t][$leg], 'distance' => '2100', 'climb' => '',
                        'controls' => count($stations),
                    ];
                }
                $runners[] = $runner;
            }
            $teams[] = [
                'id' => '',
                'uuid' => '',
                'legs' => (int)count($legs),
                'bib_number' => (string)(100 + $t),
                'team_name' => 'Team ' . $t,
                'runners' => $runners,
            ];
        }
        return [
            'configuration' => [
                'source_vendor' => 'oreplay',
                'source' => 'IofXml',
                'source_version' => '3.0',
                'contents' => 'ResultList',
                'results_type' => 'Breakdown',
                'utf' => true,
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => 'relay',
                'stages' => [[
                    'id' => StagesFixture::STAGE_FEDO_2,
                    'order_number' => 1,
                    'description' => 'relay',
                    'classes' => [[
                        'id' => '',
                        'uuid' => '',
                        'oe_key' => '1',
                        'short_name' => 'RELEVO',
                        'long_name' => 'Relevo',
                        'course' => [
                            'id' => '', 'uuid' => '', 'oe_key' => '55',
                            'short_name' => 'R1', 'distance' => '2100', 'climb' => '', 'controls' => 3,
                        ],
                        'teams' => $teams,
                        'runners' => [],
                    ]],
                ]],
            ],
        ];
    }

    public function testAddNew_shouldStoreOneCourseForEachVariantTheRunnersDeclare()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        StagesTable::load()->updateAll(
            ['stage_type_id' => StageType::RELAY],
            ['id' => StagesFixture::STAGE_FEDO_2]);

        $legs = [
            [['32', '60', '100'], ['60', '50', '100'], ['54', '60', '50', '100']],
            [['54', '60', '50', '100'], ['32', '60', '100'], ['60', '50', '100']],
        ];
        $variants = [['V1', 'V2', 'V3'], ['V3', 'V1', 'V2']];
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => $this->_relayUpload($legs, $variants)]);
        $this->assertUploadOk();

        $this->assertEquals(['RELEVO', 'V1', 'V2', 'V3'], $this->_courseShortNamesInStage(),
            'one row per variant, plus the common course the class table is drawn from');
        $this->assertEquals(['32', '60', '100'], $this->_stationsOfCourseNamed('V1'));
        $this->assertEquals(['60', '50', '100'], $this->_stationsOfCourseNamed('V2'));
        $this->assertEquals(['54', '60', '50', '100'], $this->_stationsOfCourseNamed('V3'));
        $this->assertEquals(['60', '100'], $this->_stationsOfCourseNamed('RELEVO'),
            'the class keeps the common course, so the radio list is unchanged');
    }

    private function _courseShortNamesInStage(): array
    {
        $names = CoursesTable::load()->find()
            ->where(['Courses.stage_id' => StagesFixture::STAGE_FEDO_2])
            ->all()->extract('short_name')->toList();
        sort($names);
        return $names;
    }

    private function _stationsOfCourseNamed(string $shortName): array
    {
        $course = CoursesTable::load()->find()
            ->where(['Courses.stage_id' => StagesFixture::STAGE_FEDO_2, 'Courses.short_name' => $shortName])
            ->firstOrFail();
        return CourseControlsTable::load()->find()
            ->where(['CourseControls.course_id' => $course->id])
            ->orderByAsc('CourseControls.order_number')
            ->all()->extract('station')->toList();
    }

    public function testAddNewRecordsProgressInTheUploadLog()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        ClassesTable::load()->updateAll(
            ['stage_id' => StagesFixture::STAGE_FEDO_2],
            ['id' => ClassEntity::ME]);

        $data = ['oreplay_data_transfer' => ResultExamples::resultSimpleFinishTime()];
        $this->post($this->_getEndpoint(), $data);

        $logs = UploadLogsTable::load()->find()->where(['stage_id' => StagesFixture::STAGE_FEDO_2])->all();
        $this->assertCount(1, $logs);
        $this->assertSame('1 classes, 2 participants, last 10 Mas30F', $logs->first()->info);
    }
}
