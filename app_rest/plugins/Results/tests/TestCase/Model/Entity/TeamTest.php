<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use App\Lib\Consts\StatusCodes;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;

class TeamTest extends TestCase
{
    protected array $fixtures = [
        RankingsFixture::LOAD,
    ];

    private function _member(string $firstName, string $lastName): Runner
    {
        $runner = new Runner();
        $runner->first_name = $firstName;
        $runner->last_name = $lastName;
        return $runner;
    }

    public function testToArrayWithoutID_shouldNameATeamWithItsMembers()
    {
        $team = new Team();
        $team->team_name = 'Parella alfa';
        $team->addRunner($this->_member('María del Carmen', 'García Pérez'));
        $team->addRunner($this->_member('Juan', 'López'));

        $participant = $team->toArrayWithoutID();

        $this->assertEquals('Parella alfa', $participant['first_name']);
        $this->assertEquals('(María García, Juan López)', $participant['last_name'],
            'a compound name is cut to its first word so the entry stays readable');
    }

    public function testToArrayWithoutID_shouldNameATeamWithoutMembersByItsNameAlone()
    {
        $team = new Team();
        $team->team_name = 'Parella alfa';

        $participant = $team->toArrayWithoutID();

        $this->assertEquals('Parella alfa', $participant['first_name']);
        $this->assertEquals('', $participant['last_name']);
    }

    public function testToArrayWithoutID_shouldLeaveTheBibOfTheSourceStageBehind()
    {
        $team = new Team();
        $team->team_name = 'Parella alfa';
        $team->bib_number = '301';

        $participant = $team->toArrayWithoutID();

        $this->assertArrayNotHasKey('bib_number', $participant,
            'bibs are handed out per event, so ranking a team by its bib would merge teams that '
            . 'happen to share a number in another stage');
    }

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
