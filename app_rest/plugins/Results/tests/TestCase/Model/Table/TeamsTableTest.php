<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;

class TeamsTableTest extends TestCase
{
    protected $fixtures = [
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ResultTypesFixture::LOAD,
        TeamsFixture::LOAD,
    ];
    /** @var TeamsTable Teams */
    private $Teams;

    public function setUp(): void
    {
        parent::setUp();
        $this->Teams = TeamsTable::load();
    }

    public function testFindTeamsInStage()
    {
        $RunnersTable = RunnersTable::load();
        $RunnersTable->updateAll(['team_id' => Team::FIRST_TEAM, 'leg_number' => 1],
            ['id' => Runner::FIRST_RUNNER]);
        $RunnersTable->updateAll(['team_id' => Team::FIRST_TEAM, 'leg_number' => 2],
            ['id' => RunnersFixture::RUNNER_RAID_ID]);

        $res = $this->Teams
            ->findTeamsInStage(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->all();

        $this->assertEquals(1, count($res));
        /** @var Team $team */
        $team = $res->first();
        $this->assertEquals('First Team', $team->_getFullName());
        $this->assertEquals(1, count($team['team_results']));
        $this->assertEquals(2, count($team['runners']));
        /** @var Runner $runnerA */
        $runnerA = $team['runners'][0];
        $this->assertEquals('First Runner', $runnerA->_getFullName());
        $this->assertEquals('1', $runnerA->leg_number);
        $this->assertEquals(1, count($runnerA->_getOverall()->getSplits()));
        /** @var Runner $runnerB */
        $runnerB = $team['runners'][1];
        $this->assertEquals('Second Raider', $runnerB->_getFullName());
        $this->assertEquals('2', $runnerB->leg_number);
        $this->assertEquals(null, $runnerB->_getOverall());
    }
}
