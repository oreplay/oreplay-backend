<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\PartialOverall;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;

class RunnersTableTest extends TestCase
{
    protected array $fixtures = [
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ClassesFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
    ];
    /** @var RunnersTable Runners */
    private $Runners;

    public function setUp(): void
    {
        parent::setUp();
        $this->Runners = RunnersTable::load();
    }

    public function testFindRunnersInStage_shouldReturnRunnerResult(): void
    {
        $runners = $this->Runners->findRunnersInStage(
            Event::FIRST_EVENT, Stage::FIRST_STAGE
        )->all();

        $this->assertEquals(1, $runners->count());
        /** @var Runner $runner */
        $runner = $runners->first();
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);
        $this->assertEquals('First', $runner->first_name);
        $this->assertEquals('Runner', $runner->last_name);
        $runnerResult = $runner->getResultList()[0];
        $this->assertNull($runner->team_results);
        $this->assertEquals(RunnerResult::FIRST_RES, $runnerResult->id);
        $this->assertEquals(1, $runnerResult->position);
        $this->assertEquals(310, $runnerResult->time_seconds);
        $splits = $runnerResult->getSplits();
        $splitsArray = json_decode(json_encode($splits), true);
        $expected = [
            [
                'id' => SplitsFixture::SPLIT_1,
                'is_intermediate' => false,
                'reading_time' => '2024-01-02T10:00:10.321+00:00',
                'points' => null,
                'order_number' => null,
                'created' => '2024-01-02T10:00:10.000+00:00',
                'control' => [
                    'id' => ControlsFixture::CONTROL_31,
                    'station' => '31',
                    'control_type' => null
                ]
            ],
            [
                'id' => SplitsFixture::SPLIT_1_RADIO,
                'is_intermediate' => true,
                'reading_time' => '2024-01-02T10:00:10.321+00:00',
                'points' => null,
                'order_number' => null,
                'created' => '2024-01-02T09:00:09.000+00:00',
                'control' => [
                    'id' => ControlsFixture::CONTROL_31,
                    'station' => '31',
                    'control_type' => null
                ]
            ]
        ];
        $this->assertEquals($expected, $splitsArray);
    }


    private function getMissingPunch(string $id, int $orderNumber, int $station): Split
    {
        $controlsInFixture = [
            81 => ControlsFixture::CONTROL_81,
            82 => ControlsFixture::CONTROL_82,
        ];
        $missingPunch = new Split();
        $missingPunch->id = $id;
        $missingPunch->event_id = Event::FIRST_EVENT;
        $missingPunch->stage_id = Stage::FIRST_STAGE;
        $missingPunch->is_intermediate = false;
        $missingPunch->reading_time = null;
        $missingPunch->runner_result_id = RunnerResult::FIRST_RES;
        $missingPunch->class_id = ClassEntity::ME;
        $missingPunch->control_id = $controlsInFixture[$station];
        $missingPunch->runner_id = Runner::FIRST_RUNNER;
        $missingPunch->order_number = $orderNumber;
        $missingPunch->station = $station;
        return $missingPunch;
    }

    public function testFindRunnersInStage_shouldReturnOveralls_withMP(): void
    {
        $split81 = 'f6bde838-f018-49c6-960c-61e0b68ed73b';
        $split82 = 'f729162b-a2d0-4407-8b78-b2f55c615e13';
        $this->Runners->RunnerResults->updateAll(['position' => null], ['id' => RunnerResult::FIRST_RES]);
        $Splits = $this->Runners->RunnerResults->Splits;
        $Splits->save($this->getMissingPunch($split81, 1, 81)); // 1st in course
        $Splits->updateAll(['order_number' => '1'], // 1st radio
            ['id' => SplitsFixture::SPLIT_1_RADIO]);
        $Splits->updateAll(['order_number' => '2'], // 2nd in course
            ['id' => SplitsFixture::SPLIT_1]);
        $splitWithTime = $this->getMissingPunch($split82, 3, 82); // 3rd in course
        $splitWithTime->reading_time = new FrozenTime('2025-05-13 08:13:50.814000+00:00');
        $Splits->save($splitWithTime);
        // Defined course as START -> 81 -> 31 (radio) -> 82 -> FINISH
        $runners = $this->Runners->findRunnersInStage(
            Event::FIRST_EVENT, Stage::FIRST_STAGE
        )->all();

        $this->assertEquals(1, $runners->count());
        /** @var Runner $runner */
        $runner = $runners->first();
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);
        $this->assertEquals('First', $runner->first_name);
        $this->assertEquals('Runner', $runner->last_name);
        $runnerResult = $runner->_getStage();
        $this->assertNull($runner->team_results);
        $this->assertEquals(RunnerResult::FIRST_RES, $runnerResult->id);
        $this->assertEquals(null, $runnerResult->position);
        $this->assertEquals(310, $runnerResult->time_seconds);
        $splits = $runnerResult->getSplits();
        $splitsArray = json_decode(json_encode($splits), true);
        $expected = [
            [
                'id' => $split82,
                'is_intermediate' => false,
                'reading_time' => '2025-05-13T08:13:50.814+00:00',
                'points' => null,
                'order_number' => 3,
                'control' => [
                    'id' => ControlsFixture::CONTROL_82,
                    'station' => '82',
                    'control_type' => null
                ]
            ],
            [
                'id' => SplitsFixture::SPLIT_1,
                'is_intermediate' => false,
                'reading_time' => '2024-01-02T10:00:10.321+00:00',
                'points' => null,
                'order_number' => 2,
                'control' => [
                    'id' => ControlsFixture::CONTROL_31,
                    'station' => '31',
                    'control_type' => null
                ]
            ],
            [
                'id' => $split81,
                'is_intermediate' => false,
                'reading_time' => null,
                'points' => null,
                'order_number' => 1,
                'control' => [
                    'id' => ControlsFixture::CONTROL_81,
                    'station' => '81',
                    'control_type' => null
                ]
            ],
        ];
        foreach ($splitsArray as &$split) {
            unset($split['created']);
        }
        $this->assertEquals($expected, $splitsArray);
    }

    public function testFindByCard()
    {
        /** @var Runner $res */
        $res = $this->Runners->findByCard(2009933, Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->first();
        $this->assertEquals('First', $res->first_name);
        $this->assertEquals('Runner', $res->last_name);
        $this->assertEquals(ClassEntity::ME, $res->class_id);
        $this->assertEquals(RunnerResult::FIRST_RES, $res->_getStage()->id);
    }

    public function testCreateRunnerIfNotExists_shouldKeepTheDbIdOfThePayload()
    {
        $class = new ClassEntity();
        $class->id = ClassEntity::ME;
        $data = ['db_id' => '6208', 'first_name' => 'Brand', 'last_name' => 'New'];

        $runner = $this->Runners
            ->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE, $data, $class);

        $this->assertEquals('6208', $runner->db_id);
    }

    /**
     * The desktop client sends iof_id empty on every runner, so nothing fills it today. It is
     * accessible for the IOF XML import, whose Person/Id has nowhere else to go.
     */
    public function testCreateRunnerIfNotExists_shouldKeepTheIofIdOfThePayload()
    {
        $class = new ClassEntity();
        $class->id = ClassEntity::ME;
        $data = ['iof_id' => '1234567', 'first_name' => 'Brand', 'last_name' => 'New'];

        $runner = $this->Runners
            ->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE, $data, $class);

        $this->assertEquals('1234567', $runner->iof_id);
    }

    /**
     * The whole point of db_id: the client's own identifier survives a runner being renamed or
     * given a different bib between two uploads, which neither of the other strategies can.
     */
    public function testMatchRunner_shouldFindARenamedRunnerUploadedWithTheSameDbId()
    {
        $class = new ClassEntity();
        $class->id = ClassEntity::ME;
        $firstUpload = ['db_id' => '6208', 'first_name' => 'Brand', 'last_name' => 'New'];
        $created = $this->Runners
            ->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE, $firstUpload, $class);
        $created->class_id = $class->id;
        $this->Runners->saveOrFail($created);
        $this->Runners->emptyStoredList();
        $this->Runners->getStoredAllParticipantsInClass(Event::FIRST_EVENT, Stage::FIRST_STAGE, $class->id);

        $renamed = ['db_id' => '6208', 'first_name' => 'Renamed', 'last_name' => 'Person'];
        $matched = $this->Runners->matchRunner($renamed, $class);

        $this->assertEquals($created->id, $matched->id);
    }

    private function _emptyClassWithTwoRunners(): ClassEntity
    {
        $class = new ClassEntity();
        $class->id = 'class-without-fixture-runners';
        $this->Runners->getStoredAllParticipantsInClass(Event::FIRST_EVENT, Stage::FIRST_STAGE, $class->id);
        $this->Runners->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE,
            ['db_id' => 'AAA', 'bib_number' => '11', 'first_name' => 'Ann', 'last_name' => 'One'], $class);
        $this->Runners->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE,
            ['db_id' => 'BBB', 'bib_number' => '22', 'first_name' => 'Bob', 'last_name' => 'Two'], $class);
        return $class;
    }

    /**
     * db_id is the client's own identity for the runner, so it has to beat a bib that a later upload
     * reassigned to somebody else. Matching walks the stored runners one by one, so without a pass of
     * its own the first runner holding that bib answers first and the results land on the wrong person.
     */
    public function testMatchRunner_shouldPreferTheDbIdOverTheBibOfAnEarlierRunner()
    {
        $class = $this->_emptyClassWithTwoRunners();

        $matched = $this->Runners->matchRunner(
            ['db_id' => 'BBB', 'bib_number' => '11', 'first_name' => 'X', 'last_name' => 'Y'],
            $class
        );

        $this->assertEquals('Bob', $matched->first_name);
    }

    public function testMatchRunner_shouldStillMatchByBibWhenNoDbIdMatches()
    {
        $class = $this->_emptyClassWithTwoRunners();

        $matched = $this->Runners->matchRunner(
            ['db_id' => 'unknown', 'bib_number' => '22', 'first_name' => 'X', 'last_name' => 'Y'],
            $class
        );

        $this->assertEquals('Bob', $matched->first_name);
    }

    public function testMatchRunner_shouldNotLetTheDbIdPassCrossRelayLegs()
    {
        $class = new ClassEntity();
        $class->id = 'class-without-fixture-runners';
        $this->Runners->getStoredAllParticipantsInClass(Event::FIRST_EVENT, Stage::FIRST_STAGE, $class->id);
        $this->Runners->createRunnerIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE,
            ['db_id' => 'AAA', 'leg_number' => 1, 'first_name' => 'Ann', 'last_name' => 'One'], $class);

        $otherLeg = [
            'db_id' => 'AAA',
            'first_name' => 'X',
            'last_name' => 'Y',
            'runner_results' => [['leg_number' => 2]],
        ];
        $exception = 'not raised';
        try {
            $this->Runners->matchRunner($otherLeg, $class);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }

        $this->assertEquals('Not found runner by db_id', $exception);
    }

    public function testMatchRunner()
    {
        $dbId = '984ur983u';
        $this->Runners->updateAll([
            'db_id' => 'first_db_id_not_matching',
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'class_id' => ClassEntity::ME,
        ], ['id' => RunnersFixture::RUNNER_RAID_ID]);
        $this->Runners->updateAll(['db_id' => $dbId], ['id' => Runner::FIRST_RUNNER]);
        $class = new ClassEntity();
        $class->id = ClassEntity::ME;
        $this->Runners->getStoredAllParticipantsInClass(Event::FIRST_EVENT, Stage::FIRST_STAGE, $class->id);

        // db_id found
        $data = ['db_id' => $dbId, 'sicard' => '9', 'first_name' => 'a', 'last_name' => 'b'];
        $runner = $this->Runners->matchRunner($data, $class);
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);

        // db_id not found
        $data = ['db_id' => 'badDbId', 'sicard' => '9', 'first_name' => 'a', 'last_name' => 'b'];
        $exception = 'not rised';
        try {
            $this->Runners->matchRunner($data, $class);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Not found runner by db_id', $exception);

        // bib found
        $bib = '4444';
        $data = ['bib_number' => $bib, 'sicard' => '9', 'first_name' => 'a', 'last_name' => 'b'];
        $runner = $this->Runners->matchRunner($data, $class);
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);

        // bib not found
        $data = ['bib_number' => 'bad_bib', 'sicard' => '9', 'first_name' => 'a', 'last_name' => 'b'];
        $exception = 'not rised';
        try {
            $this->Runners->matchRunner($data, $class);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Not found runner by bib_number', $exception);

        // runner found with sicard
        $data = [
            'sicard' => '2009933',
            'first_name' => 'First',
            'last_name' => 'Runner',
        ];
        $runner = $this->Runners->matchRunner($data, $class);
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);

        // runner found without sicard
        $data = [
            'sicard' => 'badSiCard',
            'first_name' => 'First',
            'last_name' => 'Runner',
        ];
        $runner = $this->Runners->matchRunner($data, $class);
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);

        // runner not found with class
        $data = [
            'sicard' => 'badSiCard',
            'first_name' => 'badName',
            'last_name' => 'Runner',
        ];
        $exception = 'not rised';
        try {
            $this->Runners->matchRunner($data, $class);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Not found runner by name', $exception);

        // runner not found without class
        $exception = 'not rised';
        try {
            $this->Runners->matchRunner($data, $class);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Not found runner by name', $exception);

        // runner found with sicard
        $classNotMatched = new ClassEntity();
        $classNotMatched->id = 'bad_id';
        $data = [
            'sicard' => '2009933',
            'first_name' => 'First',
            'last_name' => 'Runner',
        ];
        $exception = 'not rised';
        try {
            $runner = $this->Runners->matchRunner($data, $classNotMatched);
        } catch (NotFoundException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Not found runner by name', $exception);

        // runner not found
        $data = ['param' => 'badParam'];
        $exception = 'not rised';
        try {
            $this->Runners->matchRunner($data, $class);
        } catch (DetailedException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Fields first_name [] and last_name [] cannot be empty '
            . 'when bib_number and db_id is also empty {"param":"badParam"}', $exception);
    }

    public function testSortTotals()
    {
        $recalculatePosition = ScoringAlgorithm::NEEDS_POSITION;
        $runner1 = $this->_getRunner($recalculatePosition, 548);
        $runner2 = $this->_getRunner($recalculatePosition, 431);
        $toRet = [$runner1, $runner2];
        $toRet = RunnersTable::sortTotals($toRet);
        $this->assertEquals([$runner2, $runner1], $toRet);
        $this->assertEquals(1, $runner2->_getOveralls()['overall']['position']);
        $this->assertEquals(2, $runner1->_getOveralls()['overall']['position']);
    }

    private function _getRunner($pos, $time): Runner
    {
        $overall = new PartialOverall();
        $overall->result_type_id = ResultType::OVERALL;
        $overall->points_final = 12.5;
        $overall->time_seconds = $time;
        $overall->position = $pos;

        $runner1 = new Runner();
        $runner1->runner_results = [$overall];
        return $runner1;
    }

    public function testRemoveFromRanking()
    {
        $newNote = 'F-22';
        $this->Runners->removeFromRanking(Runner::FIRST_RUNNER, $newNote);

        $res = $this->Runners->RunnerResults->find()->where(['runner_id' => Runner::FIRST_RUNNER])->first();
        $this->assertEquals(Runner::FIRST_RUNNER, $res->runner_id);
        $this->assertEquals(null, $res->points_final);
        $this->assertEquals($newNote, $res->note);
        $this->assertEquals(true, $res->is_nc);
        $runner = $this->Runners->get(Runner::FIRST_RUNNER);
        $this->assertEquals(Runner::FIRST_RUNNER, $runner->id);
        $this->assertEquals('-4444', $runner->bib_number);
        $this->assertEquals(true, $runner->is_nc);
    }

    public function testMoveRunnersFromClassTo()
    {
        $this->Runners->RunnerResults->updateAll(['class_id' => ClassEntity::ME], ['id' => RunnerResult::FIRST_RES]);
        $returnedValue = $this->Runners->moveRunnersFromClassTo(ClassEntity::ME, ClassEntity::FE);
        $this->assertEquals(2, $returnedValue);
    }
}
