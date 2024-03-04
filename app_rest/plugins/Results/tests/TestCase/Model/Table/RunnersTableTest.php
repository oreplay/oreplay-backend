<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\TeamResultsFixture;

class RunnersTableTest extends TestCase
{
    protected $fixtures = [
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
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
        $runnerResult = $runner->runner_results[0];
        $this->assertNull($runner->team_results);
        $this->assertEquals(RunnerResult::FIRST_RES, $runnerResult->id);
        $this->assertEquals(1, $runnerResult->position);
        $this->assertEquals(310, $runnerResult->time_seconds);
        $split = $runnerResult->splits[0];
        $this->assertEquals(SplitsFixture::SPLIT_1, $split->id);
        $this->assertEquals(new FrozenTime('2024-01-02T10:00:10.321+00:00'), $split->reading_time);
    }
}
