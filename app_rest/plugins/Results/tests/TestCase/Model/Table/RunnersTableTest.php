<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
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
    protected $fixtures = [
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
        $runnerResult = $runner->getRunnerResults()[0];
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
        $runnerResult = $runner->_getOverall();
        $this->assertNull($runner->team_results);
        $this->assertEquals(RunnerResult::FIRST_RES, $runnerResult->id);
        $this->assertEquals(1, $runnerResult->position);
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
        $this->assertEquals(RunnerResult::FIRST_RES, $res->_getOverall()->id);
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
        $this->assertEquals('Fields first_name [] and last_name [] cannot be empty', $exception);
    }
}
