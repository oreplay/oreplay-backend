<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use DateTimeZone;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Import\Iof\IofUploadTypeDetector;
use Results\Lib\Import\Iof\IofXmlReader;
use Results\Lib\Import\Iof\ResultListMapper;

class RelayMappingTest extends TestCase
{
    private const EVENT_TIME_ZONE = 'Europe/Prague';

    private function _asset(string $name): string
    {
        return dirname(__DIR__, 4) . '/assets/iof/' . $name;
    }

    private function _relayClass(): array
    {
        $path = $this->_asset('relay.xml');
        $mapper = new ResultListMapper(IofUploadTypeDetector::detect($path), new DateTimeZone(self::EVENT_TIME_ZONE));
        foreach ((new IofXmlReader($path))->classes() as $class) {
            return $mapper->classOf($class);
        }
        return [];
    }

    public function testClassOf_shouldMapEachTeamResultToATeam()
    {
        $class = $this->_relayClass();

        $this->assertEquals([], $class['runners'], 'a relay class carries no individual results');
        $this->assertCount(3, $class['teams']);
        $names = array_map(fn($team) => $team['team_name'], $class['teams']);
        $this->assertEquals(['DKP 4', 'AOP 1', 'UOL 1'], $names);
        $this->assertEquals('241', $class['teams'][0]['bib_number']);
        $this->assertEquals('DKP', $class['teams'][0]['club']['short_name']);
    }

    /**
     * A relay carries no runners on the class: they hang off each team, one per leg, which is what
     * CourseImporter walks to build the class course (upload-courses.md 8.2).
     */
    public function testClassOf_shouldPutOneRunnerPerLegInsideTheTeam()
    {
        $team = $this->_relayClass()['teams'][0];

        $this->assertCount(4, $team['runners']);
        $legs = array_map(fn($runner) => $runner['runner_results'][0]['leg_number'], $team['runners']);
        $this->assertEquals([1, 2, 3, 4], $legs);
        $first = $team['runners'][0];
        $this->assertEquals('9200001', $first['sicard']);
        $this->assertEquals('241.1', $first['bib_number']);
        $this->assertNotEmpty($first['runner_results'][0]['splits']);
    }

    /**
     * Each leg declares its own variant, and that is the only place the variants reach the database —
     * runner_results.course_id, which the forked-class handling consumes.
     */
    public function testClassOf_shouldKeepTheCourseEachLegDeclares()
    {
        $team = $this->_relayClass()['teams'][0];

        $courses = array_map(fn($runner) => $runner['course']['short_name'], $team['runners']);
        $this->assertEquals(['241.1', '241.2', '241.3', '241.4'], $courses);
        $this->assertEquals('3100.0', $team['runners'][0]['course']['distance']);
        $this->assertEquals('70.0', $team['runners'][0]['course']['climb']);
    }

    /**
     * OverallResult is the team's cumulative standing after that leg, so it becomes one team_result per
     * leg rather than one per team.
     */
    public function testClassOf_shouldMapOverallResultToOneTeamResultPerLeg()
    {
        $team = $this->_relayClass()['teams'][0];

        $this->assertCount(4, $team['team_results']);
        $legs = array_map(fn($result) => $result['leg_number'], $team['team_results']);
        $this->assertEquals([1, 2, 3, 4], $legs);
        foreach ($team['team_results'] as $result) {
            $this->assertArrayHasKey('status_code', $result);
            $this->assertArrayHasKey('stage_order', $result);
        }
    }

    /**
     * In a team member's result TimeBehind and Position carry @type="Leg", so Xml::toArray() gives an
     * array where the individual path gets a plain string. Reading it as a number without unwrapping
     * yields 1 for every runner.
     */
    public function testClassOf_shouldReadAnAttributedTimeBehindAsItsValue()
    {
        $result = $this->_relayClass()['teams'][0]['runners'][0]['runner_results'][0];

        $this->assertEquals(156, $result['time_behind']);
        $this->assertEquals(960, $result['time_seconds']);
        $this->assertEquals(StatusCode::MP, $result['status_code']);
    }

    /**
     * QuickEvent writes offsets where SportSoftware writes none, so the offset in the document has to
     * win over the event's time zone rather than being reinterpreted in it.
     */
    public function testClassOf_shouldKeepAnOffsetTheDocumentAlreadyCarries()
    {
        $result = $this->_relayClass()['teams'][0]['runners'][0]['runner_results'][0];

        $this->assertEquals('2025-05-18T10:00:00.000+02:00', $result['start_time']);
        $this->assertEquals('2025-05-18T10:16:00.000+02:00', $result['finish_time']);
    }

    /**
     * A relay ClassResult carries no Course of its own, only one inside each leg's Result, and a class
     * with no course gets no common course stored either.
     */
    public function testClassOf_shouldFallBackToTheFirstLegsCourseForTheClass()
    {
        $class = $this->_relayClass();

        $this->assertEquals('241.1', $class['course']['short_name']);
    }

    /**
     * Some exports split one team across several TeamResult tags, one per leg, so they have to be merged
     * rather than treated as separate teams. No file in the sampled corpus does it — all 85 teams of the
     * full relay arrive whole — so this is the branch real data does not reach.
     */
    public function testClassOf_shouldMergeATeamSplitAcrossSeveralTeamResultTags()
    {
        $path = $this->_writeRelayWithTeamSplitAcrossTags();
        try {
            $mapper = new ResultListMapper(
                IofUploadTypeDetector::detect($path),
                new DateTimeZone(self::EVENT_TIME_ZONE)
            );
            foreach ((new IofXmlReader($path))->classes() as $class) {
                $teams = $mapper->classOf($class)['teams'];
                $this->assertCount(1, $teams, 'the same bib is one team');
                $this->assertCount(2, $teams[0]['runners']);
                $this->assertEquals([1, 2], array_map(
                    fn($runner) => $runner['runner_results'][0]['leg_number'],
                    $teams[0]['runners']
                ));
                $this->assertCount(2, $teams[0]['team_results']);
            }
        } finally {
            unlink($path);
        }
    }

    private function _writeRelayWithTeamSplitAcrossTags(): string
    {
        $member = function (int $leg): string {
            return '<TeamMemberResult><Person><Name><Given>a</Given><Family>b</Family></Name></Person>'
                . '<Result><Leg>' . $leg . '</Leg><BibNumber>7.' . $leg . '</BibNumber>'
                . '<StartTime>2025-05-18T10:00:00+02:00</StartTime><Status>OK</Status>'
                . '<OverallResult><Time>60</Time><Status>OK</Status></OverallResult>'
                . '</Result></TeamMemberResult>';
        };
        $teamTag = fn(string $inner) => '<TeamResult><EntryId>9</EntryId><Name>Split Team</Name>'
            . '<BibNumber>7</BibNumber>' . $inner . '</TeamResult>';
        $path = (string)tempnam(sys_get_temp_dir(), 'iof');
        file_put_contents($path, '<?xml version="1.0" encoding="UTF-8"?>'
            . '<ResultList xmlns="http://www.orienteering.org/datastandard/3.0" iofVersion="3.0"'
            . ' creator="QuickEvent 2.6"><Event><Name>split</Name></Event>'
            . '<ClassResult><Class><Id>1</Id><ShortName>R</ShortName><Name>R</Name></Class>'
            . $teamTag($member(1)) . $teamTag($member(2))
            . '</ClassResult></ResultList>');
        return $path;
    }
}
