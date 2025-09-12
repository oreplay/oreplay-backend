<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;
use Results\Model\Table\TeamResultsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;

class TeamResultsTableTest extends TestCase
{
    protected $fixtures = [
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ResultTypesFixture::LOAD,
    ];
    /** @var TeamResultsTable */
    private $TeamResults;

    public function setUp(): void
    {
        parent::setUp();
        $this->TeamResults = TeamResultsTable::load();
    }

    public function testFillNewWithStage()
    {
        $data = [
            'id' => '',
            'stage_order' => '1',
            'start_time' => '2014-07-06T13:09:01.523',
            'status_code' => '0',
            'leg_number' => '354658',
            'result_type' => [
                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                'description' => 'Stage'
            ]
        ];
        $res = $this->TeamResults->fillNewWithStage($data, Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2);
        $this->assertEquals($data['stage_order'], $res->stage_order);
        $this->assertEquals($data['leg_number'], $res->leg_number);
        $this->assertEquals($data['status_code'], $res->status_code);
        $this->assertEquals('2014-07-06T13:09:01+00:00', $res->start_time->toIso8601String());

        $res->team_id = Team::FIRST_TEAM;
        $res->result_type_id = ResultType::STAGE;
        $storedRes = $this->TeamResults->saveOrFail($res);
        /** @var TeamResult $storedRes */
        $storedRes = $this->TeamResults->get($storedRes->id);
        $this->assertEquals($data['stage_order'], $storedRes->stage_order);
        $this->assertEquals('2014-07-06T13:09:01+00:00', $storedRes->start_time->toIso8601String());
        $this->assertEquals($data['leg_number'], $storedRes->leg_number);
    }
}
