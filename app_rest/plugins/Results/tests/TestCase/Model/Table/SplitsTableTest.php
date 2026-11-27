<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;

class SplitsTableTest extends TestCase
{
    private const string LOW_ID = '00000000-0000-4000-8000-000000000001';
    private const string HIGH_ID = 'ffffffff-ffff-4fff-bfff-ffffffffffff';

    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        SplitsFixture::LOAD,
        ResultTypesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
    ];
    /** @var SplitsTable Runners */
    private $Splits;

    public function setUp(): void
    {
        parent::setUp();
        $this->Splits = SplitsTable::load();
    }

    public function testGet()
    {
        /** @var Split $res */
        $res = $this->Splits->get(SplitsFixture::SPLIT_1);

        $array = json_decode(json_encode($res), true);
        $expected = [
            'id' => SplitsFixture::SPLIT_1,
            'reading_time' => '2024-01-02T10:00:10.321+00:00',
            'points' => null,
            'is_intermediate' => false,
            'order_number' => null,
            'created' => '2024-01-02T10:00:10.000+00:00',
        ];
        $this->assertEquals($expected, $array);
    }

    public function testDeleteAllByResultIdsRemovesRunnerSplitsInOneStatement()
    {
        $deleted = $this->Splits->deleteAllByResultIds('runner_result_id', [RunnerResult::FIRST_RES]);

        $this->assertEquals(2, $deleted);
        $this->assertNull($this->Splits->findById(SplitsFixture::SPLIT_1)->first());
    }

    public function testDeleteAllByResultIdsRemovesTeamSplits()
    {
        $deleted = $this->Splits->deleteAllByResultIds('team_result_id', [TeamResultsFixture::TEAM_RESULT_1]);

        $this->assertEquals(1, $deleted);
        $this->assertNull($this->Splits->findById(SplitsFixture::SPLIT_2)->first());
    }

    public function testDeleteAllByResultIdsIgnoresAnEmptyList()
    {
        $this->assertEquals(0, $this->Splits->deleteAllByResultIds('runner_result_id', []));
        $this->assertNotNull($this->Splits->findById(SplitsFixture::SPLIT_1)->first());
    }

    public function testFindPunchesAtStation_newestFirstWithoutTheRadioCopy()
    {
        $this->assertSame([SplitsFixture::SPLIT_2, SplitsFixture::SPLIT_1], $this->_punchIdsAt('31'));
    }

    public function testFindPunchesAtStation_keepsARevisitAndARadioWithoutDownload()
    {
        $revisit = $this->_savePunch('31', '2024-01-02 10:30:00', false);
        $notDownloadedResult = $this->_saveRunnerResult(RunnersFixture::RUNNER_RAID_ID);
        $radio = $this->_savePunch('31', '2024-01-02 10:45:00', true, $notDownloadedResult);

        $expected = [SplitsFixture::SPLIT_2, $radio, $revisit, SplitsFixture::SPLIT_1];
        $this->assertSame($expected, $this->_punchIdsAt('31'));
        $punches = $this->Splits->findPunchesAtStation(Event::FIRST_EVENT, Stage::FIRST_STAGE, '31');
        $this->assertSame(4, $punches->count());
    }

    public function testFindPunchesAtStation_skipsUntimedDeletedAndOtherStations()
    {
        $this->_savePunch('31', null, true);
        $deleted = $this->_savePunch('31', '2024-01-02 10:30:00', false);
        $this->Splits->softDeleteMany([$deleted]);
        $this->_savePunch('32', '2024-01-02 10:40:00', false);

        $this->assertSame([SplitsFixture::SPLIT_2, SplitsFixture::SPLIT_1], $this->_punchIdsAt('31'));
    }

    public function testFindPunchesAtStation_skipsTheRadiosOfADownloadedRunnerWhateverTheirTime()
    {
        $this->_savePunch('31', '2024-01-02 10:00:11', true);

        $this->assertSame([SplitsFixture::SPLIT_2, SplitsFixture::SPLIT_1], $this->_punchIdsAt('31'));
    }

    public function testFindPunchesAtStation_skipsTheRadioCopyOfAPunchWithoutRunner()
    {
        $orphanIds = [SplitsFixture::SPLIT_1, SplitsFixture::SPLIT_1_RADIO];
        $this->Splits->updateAll(['runner_result_id' => null], ['id IN' => $orphanIds]);

        $this->assertSame([SplitsFixture::SPLIT_2, SplitsFixture::SPLIT_1], $this->_punchIdsAt('31'));
    }

    public function testFindPunchesAtStation_breaksTiesInTimeById()
    {
        $tiedTime = '2024-01-05 10:00:00';
        $lowId = $this->_savePunch('31', $tiedTime, false, RunnerResult::FIRST_RES, self::LOW_ID);
        $highId = $this->_savePunch('31', $tiedTime, false, RunnerResult::FIRST_RES, self::HIGH_ID);
        $this->Splits->updateAll(['created' => $tiedTime], ['id IN' => [$lowId, $highId]]);

        $this->assertSame([$highId, $lowId], array_slice($this->_punchIdsAt('31'), 0, 2));
    }

    private function _punchIdsAt(string $station): array
    {
        return $this->Splits->findPunchesAtStation(Event::FIRST_EVENT, Stage::FIRST_STAGE, $station)
            ->all()->extract('id')->toList();
    }

    private function _savePunch(
        string $station,
        ?string $readingTime,
        bool $isRadio,
        string $runnerResultId = RunnerResult::FIRST_RES,
        ?string $id = null
    ): string {
        /** @var Split $split */
        $split = $this->Splits->fillNewWithStage([
            'station' => $station,
            'reading_time' => $readingTime ? new FrozenTime($readingTime) : null,
            'is_intermediate' => $isRadio,
        ], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        if ($id) {
            $split->id = $id;
        }
        $split->runner_result_id = $runnerResultId;
        return $this->Splits->saveOrFail($split)->id;
    }

    private function _saveRunnerResult(string $runnerId): string
    {
        $RunnerResults = RunnerResultsTable::load();
        /** @var RunnerResult $result */
        $result = $RunnerResults->fillNewWithStage([], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $result->runner_id = $runnerId;
        $result->result_type_id = ResultType::STAGE;
        return $RunnerResults->saveOrFail($result)->id;
    }
}
