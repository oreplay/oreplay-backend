<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnerResultsTable;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;

class RunnerResultsTableTest extends TestCase
{
    protected $fixtures = [
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
    ];
    /** @var RunnerResultsTable Runners */
    private $RunnerResults;

    public function setUp(): void
    {
        parent::setUp();
        $this->RunnerResults = RunnerResultsTable::load();
    }

    public function testHasFinishTimes(): void
    {
        $runners = $this->RunnerResults->hasFinishTimes(Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertTrue($runners);

        $runners = $this->RunnerResults->hasFinishTimes(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2);
        $this->assertFalse($runners);
    }
}
