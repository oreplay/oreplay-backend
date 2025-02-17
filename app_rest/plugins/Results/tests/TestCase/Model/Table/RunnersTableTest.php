<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
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
        $split = $runnerResult->getSplits()[0];
        $this->assertEquals(SplitsFixture::SPLIT_1, $split->id);
        $this->assertEquals(new FrozenTime('2024-01-02T10:00:10.321+00:00'), $split->reading_time);
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
