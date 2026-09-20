<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Import\Iof\IofUploadTypeDetector;
use Results\Lib\Import\Iof\IofXmlReader;
use Results\Lib\Import\Iof\ResultListMapper;

class TeamsRunningTogetherMappingTest extends TestCase
{
    private const string EVENT_TIME_ZONE = 'Europe/Madrid';

    private function _pairsClass(): array
    {
        $path = dirname(__DIR__, 4) . '/assets/iof/pairs.xml';
        $mapper = new ResultListMapper(IofUploadTypeDetector::detect($path),
            new DateTimeZone(self::EVENT_TIME_ZONE));
        foreach ((new IofXmlReader($path))->classes() as $class) {
            return $mapper->classOf($class);
        }
        return [];
    }

    public function testClassOf_shouldKeepEveryMemberOfATeamWithoutLegs()
    {
        $teams = $this->_pairsClass()['teams'];

        $this->assertCount(2, $teams);
        $this->assertCount(2, $teams[0]['runners'],
            'a pair is two people who ran together, so both belong to the team');
        $cards = array_map(fn($runner) => $runner['sicard'], $teams[0]['runners']);
        $this->assertEquals(['9100001', '9100002'], $cards,
            'each member punched their own chip, which is what tells them apart');
    }

    public function testClassOf_shouldNotIdentifyAMemberByTheEntryIdOfItsTeam()
    {
        $runners = $this->_pairsClass()['teams'][0]['runners'];

        $dbIds = array_filter(array_map(fn($runner) => $runner['db_id'] ?? '', $runners));
        $this->assertEmpty($dbIds,
            'EntryId identifies the entry, and the file repeats the team entry on every member, so '
            . 'taking it as the person id makes the second member match the first');
    }

    public function testClassOf_shouldMapTheStandingRepeatedByEveryMemberToOneTeamResult()
    {
        $team = $this->_pairsClass()['teams'][0];

        $this->assertCount(1, $team['team_results'],
            'both members report the same standing of the same team, which is one result');
        $this->assertEquals(1, $team['team_results'][0]['position']);
        $this->assertEquals(10383, $team['team_results'][0]['time_seconds']);
        $this->assertEquals(1, $team['team_results'][0]['leg_number']);
    }

    public function testClassOf_shouldKeepTheSplitsEachMemberPunched()
    {
        $runners = $this->_pairsClass()['teams'][0]['runners'];

        $first = $runners[0]['runner_results'][0]['splits'];
        $second = $runners[1]['runner_results'][0]['splits'];
        $this->assertCount(3, $first);
        $this->assertCount(3, $second);
        $this->assertNotEquals($first[0]['reading_time'], $second[0]['reading_time'],
            'two chips read at the same control give two readings, so neither may be dropped');
    }

    public function testClassOf_shouldKeepTheTeamWithoutSplitsOfItsOwn()
    {
        $team = $this->_pairsClass()['teams'][0];

        $this->assertArrayNotHasKey('splits', $team['team_results'][0],
            'the file declares no splits for the team itself, so none are invented for it');
    }
}
