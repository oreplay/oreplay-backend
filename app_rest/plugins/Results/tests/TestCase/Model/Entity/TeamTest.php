<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use App\Lib\Consts\StatusCodes;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;

class TeamTest extends TestCase
{
    protected $fixtures = [
        RankingsFixture::LOAD,
    ];

    public function test_getFullName()
    {
        $teamResult = new Team();
        $teamResult->id = 'mainID';
        $teamResult->team_name = 'Team name';
        $teamResult->created = new FrozenTime();

        $this->assertEquals($teamResult->team_name, $teamResult->_getFullName());

        $teamResult->created = new FrozenTime('-1 year -1 day');
        $this->assertEquals($teamResult->team_name, $teamResult->_getFullName());
    }

    public function test_getStage()
    {
        $res1 = new TeamResult();
        $res1->id = 'mainID1';
        $res1->result_type_id = ResultType::STAGE;
        $res1->position = 41;
        $res1->status_code = StatusCodes::OK;
        $res1->leg_number = 1;

        $res2 = new TeamResult();
        $res2->id = 'mainID2';
        $res2->result_type_id = ResultType::STAGE;
        $res2->position = 0;
        $res2->status_code = StatusCodes::MP;
        $res2->leg_number = 2;

        $teamResult = new Team();
        $teamResult->id = 'mainID';
        $teamResult->team_name = 'Team name';
        $teamResult->created = new FrozenTime();
        $teamResult->addTeamResult($res1);
        $teamResult->addTeamResult($res2);

        $expected = [
            'id' => 'mainID2',
            'result_type_id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
            'position' => 0,
            'status_code' => StatusCodes::MP,
            'leg_number' => 2,
            'start_time' => null,
            'finish_time' => null,
        ];
        $this->assertEquals($expected, $teamResult->_getStage()->toArray());
    }
}
