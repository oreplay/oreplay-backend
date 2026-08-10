<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Cake\ORM\Query;
use Cake\Utility\Text;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Event;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\StageType;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\StagesTable;
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
use Results\Test\TestCase\Controller\UploadExamples\BigEventExamples;

class UploadsV2BigTest extends ApiCommonErrorsTest
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

    private const FIRST_STARTERS = ['PreBe' => [0, 3], 'Ben M' => [0, 2]];
    private const NEXT_FINISHERS = ['PreBe' => [3, 6], 'Ben M' => [2, 4], 'Ale M' => [0, 5]];
    private const STILL_ON_COURSE = ['Vet F' => [0, 4], 'Sen M' => [0, 3], 'ORG' => [0, 1]];

    private const PUNCHES_ON_FIRST_RADIO_BATCH = 3;
    private const PUNCHES_ON_SECOND_RADIO_BATCH = 2;

    private const LEADER_BIB = '9';
    private const MISSING_PUNCH_BIB = '100';

    private const EVENT_CLASSES = 19;
    private const EVENT_CLUBS = 19;
    private const EVENT_RUNNERS = 194;
    private const EVENT_SPLITS = 3347;
    private const EVENT_STATIONS = 31;

    private const SPLITS_STORED_BEFORE_RE_SYNC = 280;

    private string $_stageId = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->_stageId = $this->_createStageForThisRun();
    }

    private function _createStageForThisRun(): string
    {
        $stages = StagesTable::load();
        $stage = $stages->newEmptyEntity();
        $stage->id = Text::uuid();
        $stage->event_id = Event::FIRST_EVENT;
        $stage->description = BigEventExamples::EVENT_NAME;
        $stage->order_number = 1;
        $stage->stage_type_id = StageType::CLASSIC;
        $stages->saveOrFail($stage);
        return $stage->id;
    }

    protected function _getEndpointAddingToSwagger(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/uploads/v2/';
    }

    protected function _getEndpoint(): string
    {
        $this->skipNextRequestInSwagger();
        return $this->_getEndpointAddingToSwagger();
    }

    public function testAddNew_shouldReplayAWholeRealEvent()
    {
        Cache::clear();

        $this->_firstRadiosWhileTheyRun();
        $this->_sameRunnersDownloadTheirCards();
        $this->_moreRunnersFinishInOldAndNewClasses();
        $this->_secondRadioBatchForRunnersStillOut();
        $this->_organiserReSyncsTheWholeEvent();
        $this->_reUploadingTheWholeEventChangesNothing();
    }

    private function _firstRadiosWhileTheyRun(): void
    {
        $meta = $this->_upload(
            BigEventExamples::radioPunches(self::FIRST_STARTERS, self::PUNCHES_ON_FIRST_RADIO_BATCH, $this->_stageId)
        );
        $this->_assertUpdated([
            'classes' => 2,
            'courses' => 2,
            'runners' => 5,
            'splits' => 15,
            'runnerResults' => 5,
        ], $meta, 'radios while they run');
        $this->_assertDatabase([
            'classes' => 2,
            'courses' => 2,
            'clubs' => 2,
            'runners' => 5,
            'runnerResults' => 5,
            'splits' => 15,
            'controls' => 5,
        ], 'radios while they run');
        $this->assertEquals(15, $this->_intermediateSplitAmount(), 'every radio punch is intermediate');

        $leader = $this->_resultOfBib(self::LEADER_BIB);
        $this->assertEquals(UploadTypes::INTERMEDIATES, $leader->upload_type);
        $this->assertNull($leader->finish_time, 'a runner on course has no finish time yet');
        $this->assertEquals(['48', '49', '57'], $this->_stationsOf($leader));
        $this->assertEquals([
            '2024-11-17T11:20:11+00:00',
            '2024-11-17T11:23:09+00:00',
            '2024-11-17T11:30:17+00:00',
        ], $this->_readingTimesOf($leader), 'reading_time is start_time plus the cumulative IOF split time');
    }

    private function _sameRunnersDownloadTheirCards(): void
    {
        $meta = $this->_upload(BigEventExamples::downloadedCards(self::FIRST_STARTERS, $this->_stageId));
        $this->_assertUpdated([
            'classes' => 2,
            'courses' => 2,
            'runners' => 5,
            'splits' => 70,
            'runnerResults' => 5,
        ], $meta, 'download after radios');
        $this->_assertDatabase([
            'classes' => 2,
            'courses' => 2,
            'clubs' => 2,
            'runners' => 5,
            'runnerResults' => 5,
            'splits' => 70,
            'controls' => 16,
        ], 'download after radios');
        $this->assertEquals(0, $this->_intermediateSplitAmount(),
            'the download replaces the radio punches instead of duplicating them');

        $leader = $this->_resultOfBib(self::LEADER_BIB);
        $this->assertEquals(UploadTypes::SPLITS, $leader->upload_type);
        $this->assertEquals(14, count($leader->splits), 'the whole card, not the card plus the radios');
        $this->assertEquals('2024-11-17T12:00:54+00:00', $leader->finish_time->toIso8601String());
        $this->assertEquals(2585, $leader->time_seconds);
        $this->assertEquals(1, $leader->position);
        $this->assertEquals(StatusCode::OK, $leader->status_code);
        $this->assertEquals(['48', '49', '57'], array_slice($this->_stationsOf($leader), 0, 3));
    }

    private function _moreRunnersFinishInOldAndNewClasses(): void
    {
        $meta = $this->_upload(BigEventExamples::downloadedCards(self::NEXT_FINISHERS, $this->_stageId));
        $this->_assertUpdated([
            'classes' => 3,
            'courses' => 3,
            'runners' => 15,
            'splits' => 210,
            'runnerResults' => 15,
        ], $meta, 'more finishers');
        $this->_assertDatabase([
            'classes' => 3,
            'courses' => 3,
            'clubs' => 8,
            'runners' => 20,
            'runnerResults' => 20,
            'splits' => 280,
            'controls' => 21,
        ], 'more finishers');
        $this->assertEquals(0, $this->_intermediateSplitAmount());

        $mispunched = $this->_resultOfBib(self::MISSING_PUNCH_BIB);
        $this->assertEquals(StatusCode::MP, $mispunched->status_code);
        $this->assertEquals(14, count($mispunched->splits));
        $this->assertEquals(1, count($this->_splitsWithoutReadingTime($mispunched)),
            'the control the runner missed is kept as a punch without reading_time');
    }

    private function _secondRadioBatchForRunnersStillOut(): void
    {
        $meta = $this->_upload(
            BigEventExamples::radioPunches(self::STILL_ON_COURSE, self::PUNCHES_ON_SECOND_RADIO_BATCH, $this->_stageId)
        );
        $this->_assertUpdated([
            'classes' => 3,
            'courses' => 3,
            'runners' => 8,
            'splits' => 15,
            'runnerResults' => 8,
        ], $meta, 'second radio batch');
        $this->_assertDatabase([
            'classes' => 6,
            'courses' => 6,
            'clubs' => 11,
            'runners' => 28,
            'runnerResults' => 28,
            'splits' => 295,
            'controls' => 21,
        ], 'second radio batch');
        $this->assertEquals(15, $this->_intermediateSplitAmount());
    }

    private function _organiserReSyncsTheWholeEvent(): void
    {
        $meta = $this->_upload(BigEventExamples::wholeEvent($this->_stageId));
        $this->_assertUpdated([
            'classes' => self::EVENT_CLASSES,
            'courses' => self::EVENT_CLASSES,
            'runners' => self::EVENT_RUNNERS,
            'splits' => self::EVENT_SPLITS - self::SPLITS_STORED_BEFORE_RE_SYNC,
            'runnerResults' => self::EVENT_RUNNERS,
        ], $meta, 'whole event re-sync');
        $this->_assertDatabase([
            'classes' => self::EVENT_CLASSES,
            'courses' => self::EVENT_CLASSES,
            'clubs' => self::EVENT_CLUBS,
            'runners' => self::EVENT_RUNNERS,
            'runnerResults' => self::EVENT_RUNNERS,
            'splits' => self::EVENT_SPLITS,
            'controls' => self::EVENT_STATIONS,
        ], 'whole event re-sync');
        $this->assertEquals(0, $this->_intermediateSplitAmount(),
            'the re-sync replaces every radio punch of the runners still out');
        $this->assertEquals([
            StatusCode::OK => 176,
            StatusCode::DNF => 13,
            StatusCode::MP => 5,
        ], $this->_resultAmountByStatus());
    }

    private function _reUploadingTheWholeEventChangesNothing(): void
    {
        $meta = $this->_uploadWithColdCache(BigEventExamples::wholeEvent($this->_stageId));
        $this->_assertUpdated([
            'classes' => 0,
            'courses' => 0,
            'runners' => 0,
            'splits' => 0,
            'runnerResults' => 0,
        ], $meta, 'identical re-upload');
        $this->_assertDatabase([
            'classes' => self::EVENT_CLASSES,
            'courses' => self::EVENT_CLASSES,
            'clubs' => self::EVENT_CLUBS,
            'runners' => self::EVENT_RUNNERS,
            'runnerResults' => self::EVENT_RUNNERS,
            'splits' => self::EVENT_SPLITS,
            'controls' => self::EVENT_STATIONS,
        ], 'identical re-upload');
    }

    private function _uploadWithColdCache(array $dataTransfer): array
    {
        Cache::disable();
        try {
            return $this->_upload($dataTransfer);
        } finally {
            Cache::enable();
        }
    }

    private function _upload(array $dataTransfer): array
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->post($this->_getEndpoint(), ['oreplay_data_transfer' => $dataTransfer]);
        return $this->assertJsonResponseOK()['meta'];
    }

    private function _assertUpdated(array $expected, array $meta, string $step): void
    {
        $this->assertEquals($expected, $meta['updated'], $step . ' ' . implode(' ', $meta['human']));
    }

    private function _assertDatabase(array $expected, string $step): void
    {
        $this->assertEquals($expected, [
            'classes' => $this->_amountInStage(ClassesTable::load()),
            'courses' => $this->_amountInStage(CoursesTable::load()),
            'clubs' => $this->_amountInStage(ClubsTable::load()),
            'runners' => $this->_amountInStage(RunnersTable::load()),
            'runnerResults' => $this->_amountInStage(RunnerResultsTable::load()),
            'splits' => $this->_amountInStage(SplitsTable::load()),
            'controls' => $this->_amountInStage(ControlsTable::load()),
        ], $step);
    }

    private function _amountInStage($table): int
    {
        return $table->find()->where(['stage_id' => $this->_stageId])->all()->count();
    }

    private function _intermediateSplitAmount(): int
    {
        return SplitsTable::load()->find()
            ->where(['stage_id' => $this->_stageId, 'is_intermediate' => true])
            ->all()->count();
    }

    private function _resultAmountByStatus(): array
    {
        $amountByStatus = [];
        $results = RunnerResultsTable::load()->find()
            ->where(['stage_id' => $this->_stageId])->all();
        /** @var RunnerResult $result */
        foreach ($results as $result) {
            $status = (string)$result->status_code;
            $amountByStatus[$status] = ($amountByStatus[$status] ?? 0) + 1;
        }
        return $amountByStatus;
    }

    private function _resultOfBib(string $bibNumber): RunnerResult
    {
        $runner = RunnersTable::load()->find()
            ->where(['stage_id' => $this->_stageId, 'bib_number' => $bibNumber])
            ->first();
        $this->assertNotNull($runner, 'Runner with bib ' . $bibNumber);
        /** @var RunnerResult $result */
        $result = RunnerResultsTable::load()->find()
            ->where(['runner_id' => $runner->id])
            ->contain(SplitsTable::name(), function (Query $q) {
                return $q->orderByAsc('order_number');
            })
            ->first();
        $this->assertNotNull($result, 'Result of bib ' . $bibNumber);
        return $result;
    }

    private function _stationsOf(RunnerResult $result): array
    {
        return array_map(fn($split) => (string)$split->station, $result->splits);
    }

    private function _readingTimesOf(RunnerResult $result): array
    {
        return array_map(fn($split) => $split->reading_time->toIso8601String(), $result->splits);
    }

    private function _splitsWithoutReadingTime(RunnerResult $result): array
    {
        return array_values(array_filter($result->splits, fn($split) => $split->reading_time === null));
    }
}
