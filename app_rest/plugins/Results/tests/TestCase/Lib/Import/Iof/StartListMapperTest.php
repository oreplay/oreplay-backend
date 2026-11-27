<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\Import\Iof\IofUploadTypeDetector;
use Results\Lib\Import\Iof\IofXmlReader;
use Results\Lib\Import\Iof\ResultListMapper;
use Results\Lib\Import\Iof\StartListMapper;
use Results\Model\Entity\ResultType;

class StartListMapperTest extends TestCase
{
    private const EVENT_TIME_ZONE = 'Europe/Madrid';

    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/iof/' . $name;
    }

    private function _mappedClasses(string $asset): array
    {
        $header = IofUploadTypeDetector::detect($this->_asset($asset));
        $mapper = new StartListMapper($header, new DateTimeZone(self::EVENT_TIME_ZONE));
        $mapped = [];
        foreach ((new IofXmlReader($this->_asset($asset), 'ClassStart'))->classes() as $class) {
            $mapped[] = $mapper->classOf($class);
        }
        return $mapped;
    }

    public function testDetect_shouldReadAStartListAsAStartList()
    {
        $this->assertEquals(UploadTypes::START_LIST,
            IofUploadTypeDetector::detect($this->_asset('starts.xml'))->getUploadType());
    }

    public function testClassOf_shouldMapTheClassAndItsCourseLikeAResultList()
    {
        $classes = $this->_mappedClasses('starts.xml');

        $this->assertCount(1, $classes);
        $this->assertEquals('E', $classes[0]['short_name']);
        $this->assertEquals('1', $classes[0]['oe_key']);
        $this->assertEquals('2000.0', $classes[0]['course']['distance']);
        $this->assertEquals('50.0', $classes[0]['course']['climb']);
        $this->assertEquals(5, $classes[0]['course']['controls']);
        $this->assertEquals([], $classes[0]['teams']);
    }

    /**
     * A start list carries the entry and its start time and nothing else: no Status, no SplitTime, no
     * Position. The shape still has to match what the JSON path receives for a start list, or the same
     * stage would look different depending on the format it arrived in.
     */
    public function testClassOf_shouldGiveEachRunnerOneResultWithOnlyAStartTime()
    {
        $runners = $this->_mappedClasses('starts.xml')[0]['runners'];

        $this->assertCount(3, $runners);
        $first = $runners[0];
        $this->assertEquals('mailtt', $first['first_name']);
        $this->assertEquals('9000001', $first['sicard']);
        $this->assertEquals('1', $first['bib_number']);
        $this->assertFalse($first['is_nc']);

        $this->assertCount(1, $first['runner_results']);
        $result = $first['runner_results'][0];
        $this->assertEquals('2025-03-26T08:00:00.000+01:00', $result['start_time']);
        $this->assertEquals(StatusCode::OK, $result['status_code']);
        $this->assertEquals(1, $result['stage_order']);
        $this->assertEquals(1, $result['leg_number']);
        $this->assertEquals(ResultType::STAGE, $result['result_type']['id']);
        $this->assertEquals([], $result['splits']);
        $this->assertArrayNotHasKey('finish_time', $result);
        $this->assertArrayNotHasKey('time_seconds', $result);
        $this->assertArrayNotHasKey('position', $result);
    }

    public function testClassOf_shouldMapARelayStartListToTeamsWithOneRunnerPerLeg()
    {
        $expectedTeams = [
            ['101', 'Club Uno Infantil 01', 'Club Uno', ['9100001', '9100002'], [1, 2]],
            ['102', 'Club Dos Infantil 01', 'Club Dos', ['9100003', '9100004'], [1, 2]],
        ];

        $class = $this->_mappedClasses('relay_starts.xml')[0];

        $this->assertEquals([], $class['runners'], 'a relay class carries no individual entries');
        $teams = array_map(fn($team) => [
            $team['bib_number'],
            $team['team_name'],
            $team['club']['short_name'],
            array_column($team['runners'], 'sicard'),
            array_map(fn($runner) => $runner['runner_results'][0]['leg_number'], $team['runners']),
        ], $class['teams']);
        $this->assertEquals($expectedTeams, $teams);
    }

    public function testClassOf_shouldStartOnlyTheFirstLegOfARelayAtItsStartTime()
    {
        $expectedStartTimes = ['2026-11-28T11:00:00.000+01:00', null];

        $team = $this->_mappedClasses('relay_starts.xml')[0]['teams'][0];

        $startTimes = array_map(fn($runner) => $runner['runner_results'][0]['start_time'] ?? null, $team['runners']);
        $this->assertEquals($expectedStartTimes, $startTimes);
    }

    public function testClassOf_shouldGiveARelayTeamTheStartOfItsFirstLegAsItsOnlyResult()
    {
        $expectedTeamResults = [[
            'id' => '',
            'stage_order' => 1,
            'status_code' => StatusCode::OK,
            'leg_number' => 1,
            'result_type' => ['id' => ResultType::STAGE, 'description' => 'Stage'],
            'start_time' => '2026-11-28T11:00:00.000+01:00',
        ]];

        $team = $this->_mappedClasses('relay_starts.xml')[0]['teams'][0];

        $this->assertEquals($expectedTeamResults, $team['team_results']);
    }

    public function testClassOf_shouldKeepTheCourseEachRelayLegRuns()
    {
        $class = $this->_mappedClasses('relay_starts.xml')[0];

        $legCourses = array_map(fn($runner) => $runner['course']['short_name'], $class['teams'][0]['runners']);
        $this->assertEquals(['#1', '#2'], $legCourses);
        $this->assertEquals('#1', $class['course']['short_name'], 'the first leg stands in for the class course');
    }

    public function testClassOf_shouldGiveARelayLegWithoutAClubTheClubOfItsTeam()
    {
        $team = $this->_mappedClasses('relay_starts.xml')[0]['teams'][0];
        $expectedClubs = [$team['club'], $team['club']];

        $legClubs = array_map(fn($runner) => $runner['club'] ?? null, $team['runners']);

        $this->assertEquals($expectedClubs, $legClubs);
    }

    public function testClassOf_shouldKeepTheClubOfEachEntry()
    {
        $runners = $this->_mappedClasses('starts.xml')[0]['runners'];

        $this->assertEquals('A Coruña Liceo', $runners[0]['club']['short_name']);
        $this->assertEquals('1', $runners[0]['club']['oe_key']);
    }

    public function testClassOf_shouldLeaveTheAlternateCardEmptyWhenThereIsOnlyOne()
    {
        $runners = $this->_mappedClasses('starts.xml')[0]['runners'];

        $this->assertEquals('9000001', $runners[0]['sicard']);
        $this->assertEquals('', $runners[0]['sicard_alt']);
    }

    /**
     * A competitor may carry a reserve chip, and SiTiming writes a whole team as one person with several
     * cards. Both upload types keep the second one.
     */
    public function testClassOf_shouldTakeASecondControlCardAsTheAlternate()
    {
        $path = $this->_writeTemporaryXml('StartList', 'PersonStart', 'Start');
        try {
            $header = IofUploadTypeDetector::detect($path);
            $mapper = new StartListMapper($header, new DateTimeZone(self::EVENT_TIME_ZONE));
            foreach ((new IofXmlReader($path, 'ClassStart'))->classes() as $class) {
                $runner = $mapper->classOf($class)['runners'][0];
                $this->assertEquals('111', $runner['sicard']);
                $this->assertEquals('222', $runner['sicard_alt']);
            }
        } finally {
            unlink($path);
        }
    }

    /**
     * The same has to hold for a result list, which used to drop the reserve chip.
     */
    public function testClassOf_shouldTakeASecondControlCardOnAResultListToo()
    {
        $path = $this->_writeTemporaryXml('ResultList', 'PersonResult', 'Result');
        try {
            $header = IofUploadTypeDetector::detect($path);
            $mapper = new ResultListMapper($header, new DateTimeZone(self::EVENT_TIME_ZONE));
            foreach ((new IofXmlReader($path))->classes() as $class) {
                $runner = $mapper->classOf($class)['runners'][0];
                $this->assertEquals('111', $runner['sicard']);
                $this->assertEquals('222', $runner['sicard_alt']);
            }
        } finally {
            unlink($path);
        }
    }

    private function _writeTemporaryXml(string $root, string $person, string $result): string
    {
        $classElement = $root === 'StartList' ? 'ClassStart' : 'ClassResult';
        $path = (string)tempnam(sys_get_temp_dir(), 'iof');
        file_put_contents($path, '<?xml version="1.0" encoding="UTF-8"?>'
            . '<' . $root . ' xmlns="http://www.orienteering.org/datastandard/3.0" iofVersion="3.0"'
            . ' creator="SiTiming v4"><Event><Name>two cards</Name></Event>'
            . '<' . $classElement . '><Class><Id>1</Id><ShortName>E</ShortName><Name>E</Name></Class>'
            . '<' . $person . '><Person><Name><Given>a</Given><Family>b</Family></Name></Person>'
            . '<' . $result . '><BibNumber>1</BibNumber><StartTime>2025-03-26T08:00:00.000</StartTime>'
            . '<ControlCard>111</ControlCard><ControlCard>222</ControlCard>'
            . '</' . $result . '></' . $person . '></' . $classElement . '></' . $root . '>');
        return $path;
    }
}
