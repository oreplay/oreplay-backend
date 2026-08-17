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

    /**
     * A relay class carries teams instead of individual entries. Only a result list has them.
     */
    protected function teamsOf(array $data): array
    {
        return [];
    }

    public function classOf(IofClassResult $classResult): array
    {
        $data = $classResult->getData();
        $class = $data['Class'] ?? [];
        $teams = $this->teamsOf($data);
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)($class['Id'] ?? ''),
            'short_name' => (string)($class['ShortName'] ?? ''),
            'long_name' => (string)($class['Name'] ?? ''),
            'teams' => $teams,
            'course' => $this->courseOf($data['Course'] ?? []) ?: $this->_courseOfFirstLeg($teams),
            'runners' => $this->_runners($data[$this->personElement()] ?? []),
        ];
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
