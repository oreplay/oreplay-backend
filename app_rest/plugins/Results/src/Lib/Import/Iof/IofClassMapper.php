<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use DateTimeImmutable;
use DateTimeZone;

/**
 * What a ClassResult and a ClassStart have in common, which is everything except the result itself: the
 * class, its course, the entries, their clubs. A port of the shared parts of ConverterIofToModel from
 * the Java desktop client — see docs/upload-xml-input.md 2B.
 */
abstract class IofClassMapper
{
    protected const DATE_FORMAT = 'Y-m-d\TH:i:s.vP';
    private const MEOS = 'MeOS';

    /**
     * MeOS reports penalties as positive numbers where every other producer reports them negative.
     */
    protected int $_penaltyFactor;

    public function __construct(
        protected readonly IofHeader $header,
        protected readonly DateTimeZone $timeZone
    ) {
        $this->_penaltyFactor = str_contains($this->header->getCreator(), self::MEOS) ? -1 : 1;
    }

    /** The element holding one entry: PersonResult or PersonStart. */
    abstract protected function personElement(): string;

    /** The element holding that entry's result: Result or Start. */
    abstract protected function resultElement(): string;

    abstract protected function resultOf(array $result, array $runner): array;

    abstract protected function teamElement(): string;

    abstract protected function teamMemberElement(): string;

    abstract protected function teamResultsOf(array $members): array;

    /**
     * The same team may be split across several team tags — one per leg in some exports — so they are
     * merged on the first identifier that is present. A team member's result is shaped exactly like an
     * individual entry's, so the entry mapping is reused unchanged.
     */
    private function _teamsOf(array $data): array
    {
        $teams = [];
        $membersOfTeam = [];
        foreach (IofNode::listOf($data[$this->teamElement()] ?? []) as $teamNode) {
            $key = self::_teamKeyOf($teamNode);
            $teams[$key] ??= $this->_newTeam($teamNode);
            foreach (IofNode::listOf($teamNode[$this->teamMemberElement()] ?? []) as $member) {
                $teams[$key]['runners'][] = $this->_teamMemberOf($member);
                $membersOfTeam[$key][] = $member;
            }
        }
        foreach ($teams as $key => $team) {
            $teams[$key]['team_results'] = $this->teamResultsOf($membersOfTeam[$key] ?? []);
        }
        return array_values($teams);
    }

    private static function _teamKeyOf(array $teamNode): string
    {
        $bib = (string)($teamNode['BibNumber'] ?? '');
        $entryId = IofNode::repeatedTextOf($teamNode['EntryId'] ?? null, 0);
        $name = (string)($teamNode['Name'] ?? '');
        return $bib ?: ($entryId ?: $name);
    }

    private function _newTeam(array $teamNode): array
    {
        $team = [
            'id' => '',
            'uuid' => '',
            'bib_number' => (string)($teamNode['BibNumber'] ?? ''),
            'team_name' => (string)($teamNode['Name'] ?? ''),
            'runners' => [],
            'team_results' => [],
        ];
        $club = $this->clubOf($teamNode['Organisation'] ?? []);
        if ($club) {
            $team['club'] = $club;
        }
        return $team;
    }

    /**
     * Each leg declares the variant that leg ran, and this is the only place those courses reach the
     * database: runner_results.course_id, which the forked-class handling consumes.
     */
    private function _teamMemberOf(array $member): array
    {
        $runner = $this->runnerOf($member);
        if (!$this->_declaresALeg($member)) {
            $runner = self::_withoutTheEntryIdOfItsTeam($runner, $member);
        }
        $course = $this->courseOf($this->legOf($member)['Course'] ?? []);
        if ($course) {
            $runner['course'] = $course;
        }
        return $runner;
    }

    protected function legOf(array $member): array
    {
        return IofNode::listOf($member[$this->resultElement()] ?? [])[0] ?? [];
    }

    private function _declaresALeg(array $member): bool
    {
        return ($this->legOf($member)['Leg'] ?? '') !== '';
    }

    private static function _withoutTheEntryIdOfItsTeam(array $runner, array $member): array
    {
        $personId = IofNode::repeatedTextOf(($member['Person'] ?? [])['Id'] ?? null, 0);
        if ($personId === '') {
            unset($runner['db_id']);
            return $runner;
        }
        $runner['db_id'] = $personId;
        return $runner;
    }

    public function classOf(IofClassResult $classResult): array
    {
        $data = $classResult->getData();
        $class = $data['Class'] ?? [];
        $teams = $this->_teamsOf($data);
        $mapped = [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)($class['Id'] ?? ''),
            'short_name' => (string)($class['ShortName'] ?? ''),
            'long_name' => (string)($class['Name'] ?? ''),
            'teams' => $teams,
            'course' => $this->courseOf($data['Course'] ?? []) ?: $this->_courseOfFirstLeg($teams),
            'runners' => $this->_runners($data[$this->personElement()] ?? []),
        ];
        return self::_withDeclaredRadios($mapped, $classResult->getRadioStations());
    }

    /**
     * @param string[] $radioStations
     */
    private static function _withDeclaredRadios(array $mapped, array $radioStations): array
    {
        if ($radioStations) {
            $mapped['classes_controls'] = array_map(
                fn(string $station) => ['control' => ['station' => $station]],
                $radioStations
            );
        }
        return $mapped;
    }

    protected function courseOf(mixed $course): array
    {
        $course = IofNode::firstOf($course);
        if (!$course) {
            return [];
        }
        return [
            'id' => '',
            'uuid' => '',
            'distance' => ClientNumberFormat::distance($course['Length'] ?? null),
            'climb' => ClientNumberFormat::distance($course['Climb'] ?? null),
            'controls' => (int)($course['NumberOfControls'] ?? 0),
            'oe_key' => (string)($course['Id'] ?? ''),
            'short_name' => (string)($course['Name'] ?? ''),
        ];
    }

    /**
     * A relay declares no course on the class, only one per leg, and a class with no course at all gets no
     * common course stored either. The first leg's stands in, as the desktop client does; once the runners
     * turn out to declare more than one, CoursesTable::createForClassIfNotExists() replaces it with a
     * course of the class's own (upload-courses.md 8.2).
     */
    private function _courseOfFirstLeg(array $teams): array
    {
        foreach ($teams as $team) {
            foreach ($team['runners'] as $runner) {
                if ($runner['course'] ?? []) {
                    return $runner['course'];
                }
            }
        }
        return [];
    }

    private function _runners(array $entries): array
    {
        $runners = [];
        foreach (IofNode::listOf($entries) as $entry) {
            $runners[] = $this->runnerOf($entry);
        }
        return $runners;
    }

    protected function runnerOf(array $entry): array
    {
        $person = $entry['Person'] ?? [];
        $name = $person['Name'] ?? [];
        $results = IofNode::listOf($entry[$this->resultElement()] ?? []);
        $first = $results[0] ?? [];
        $runner = [
            'id' => '',
            'uuid' => '',
            // a competitor may carry a reserve chip; SiTiming also writes a whole team as one person
            // with several cards
            'sicard' => IofNode::repeatedTextOf($first['ControlCard'] ?? null, 0),
            'sex' => (string)($person['@sex'] ?? 'M'),
            'first_name' => (string)($name['Given'] ?? ''),
            'last_name' => (string)($name['Family'] ?? ''),
            'bib_number' => (string)($first['BibNumber'] ?? ''),
            'sicard_alt' => IofNode::repeatedTextOf($first['ControlCard'] ?? null, 1),
            'is_nc' => NcStatusReconstructor::isNotCompeting(self::statusOf($first)),
        ];
        $dbId = $this->_dbIdOf($entry, $person);
        if ($dbId !== '') {
            $runner['db_id'] = $dbId;
        }
        $runner['runner_results'] = $this->_results($results, $runner);
        $club = $this->clubOf($entry['Organisation'] ?? []);
        if ($club) {
            $runner['club'] = $club;
        }
        return $runner;
    }

    /**
     * EntryId is the client's own database id and is preferred; Person/Id is the fallback the desktop
     * client also accepts.
     */
    private function _dbIdOf(array $entry, array $person): string
    {
        $entryId = IofNode::repeatedTextOf($entry['EntryId'] ?? null, 0);
        if ($entryId !== '') {
            return $entryId;
        }
        return IofNode::repeatedTextOf($person['Id'] ?? null, 0);
    }

    protected function clubOf(mixed $organisation): array
    {
        $organisation = IofNode::firstOf($organisation);
        if (!$organisation) {
            return [];
        }
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)($organisation['Id'] ?? ''),
            'short_name' => (string)($organisation['ShortName'] ?? ''),
            'long_name' => (string)($organisation['Name'] ?? ''),
        ];
    }

    private function _results(array $results, array $runner): array
    {
        $mapped = [];
        foreach ($results as $result) {
            $mapped[] = $this->resultOf($result, $runner);
        }
        return $mapped;
    }

    protected static function statusOf(array $result): ?string
    {
        $status = $result['Status'] ?? null;
        return $status === null ? null : (string)$status;
    }

    protected function dateOf(mixed $value): ?DateTimeImmutable
    {
        if (!$value || !is_string($value)) {
            return null;
        }
        return new DateTimeImmutable($value, $this->timeZone);
    }

    protected static function stageOrderOf(array $result): int
    {
        return (int)($result['@raceNumber'] ?? 1);
    }
}
