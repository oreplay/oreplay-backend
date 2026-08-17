<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use DateTimeImmutable;
use DateTimeZone;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Split;

/**
 * Turns one IOF ClassResult into the classes[] array the importers already consume. A port of
 * ConverterIofToModel.convertResultListSingleStageClassic() from the Java desktop client; the field
 * table it follows, and the places it deliberately differs, are in docs/upload-xml-input.md 2B.
 */
class ResultListMapper
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:s.vP';
    private const MEOS = 'MeOS';

    private int $_penaltyFactor;

    public function __construct(
        private readonly IofHeader $header,
        private readonly DateTimeZone $timeZone
    ) {
        // MeOS reports penalties as positive numbers where every other producer reports them negative
        $this->_penaltyFactor = str_contains($this->header->getCreator(), self::MEOS) ? -1 : 1;
    }

    public function classOf(IofClassResult $classResult): array
    {
        $data = $classResult->getData();
        $class = $data['Class'] ?? [];
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)($class['Id'] ?? ''),
            'short_name' => (string)($class['ShortName'] ?? ''),
            'long_name' => (string)($class['Name'] ?? ''),
            'teams' => [],
            'course' => $this->_course($data['Course'] ?? []),
            'runners' => $this->_runners($data['PersonResult'] ?? []),
        ];
    }

    private function _course(array $course): array
    {
        $course = self::firstOf($course);
        if (!$course) {
            return [];
        }
        return [
            'id' => '',
            'uuid' => '',
            'distance' => self::distanceOf($course['Length'] ?? null),
            'climb' => self::distanceOf($course['Climb'] ?? null),
            'controls' => (int)($course['NumberOfControls'] ?? 0),
            'oe_key' => (string)($course['Id'] ?? ''),
            'short_name' => (string)($course['Name'] ?? ''),
        ];
    }

    private function _runners(array $personResults): array
    {
        $runners = [];
        foreach (self::listOf($personResults) as $personResult) {
            $runners[] = $this->_runner($personResult);
        }
        return $runners;
    }

    private function _runner(array $personResult): array
    {
        $person = $personResult['Person'] ?? [];
        $name = $person['Name'] ?? [];
        $results = self::listOf($personResult['Result'] ?? []);
        $first = $results[0] ?? [];
        $runner = [
            'id' => '',
            'uuid' => '',
            'sicard' => $this->_firstControlCard($first),
            'sex' => (string)($person['@sex'] ?? 'M'),
            'first_name' => (string)($name['Given'] ?? ''),
            'last_name' => (string)($name['Family'] ?? ''),
            'bib_number' => (string)($first['BibNumber'] ?? ''),
            'sicard_alt' => '',
            'is_nc' => NcStatusReconstructor::isNotCompeting($this->_statusOf($first)),
        ];
        $dbId = $this->_dbIdOf($personResult, $person);
        if ($dbId !== '') {
            $runner['db_id'] = $dbId;
        }
        $runner['runner_results'] = $this->_results($results, $runner);
        $club = $this->_club($personResult['Organisation'] ?? []);
        if ($club) {
            $runner['club'] = $club;
        }
        return $runner;
    }

    /**
     * EntryId is the client's own database id and is preferred; Person/Id is the fallback the desktop
     * client also accepts.
     */
    private function _dbIdOf(array $personResult, array $person): string
    {
        $entryId = self::scalarOf(self::listOf($personResult['EntryId'] ?? null)[0] ?? null);
        if ($entryId !== '') {
            return $entryId;
        }
        return self::scalarOf(self::listOf($person['Id'] ?? null)[0] ?? null);
    }

    private function _club(array $organisation): array
    {
        $organisation = self::firstOf($organisation);
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
            $mapped[] = $this->_result($result, $runner);
        }
        return $mapped;
    }

    private function _result(array $result, array $runner): array
    {
        $stageOrder = (int)($result['@raceNumber'] ?? 1);
        $startTime = $this->_dateOf($result['StartTime'] ?? null);
        $finishTime = $this->_dateOf($result['FinishTime'] ?? null);
        $position = isset($result['Position']) ? (int)$result['Position'] : null;
        $splits = $this->_splits($result, $runner, $stageOrder, $startTime);
        $mapped = [
            'id' => '',
            'stage_order' => $stageOrder,
            'status_code' => NcStatusReconstructor::codeOf(
                $this->_statusOf($result),
                $position,
                $startTime !== null,
                $finishTime !== null,
                $this->_hasMissingSplit($splits)
            ),
            'time_neutralization' => 0,
            'time_adjusted' => 0,
            'time_penalty' => 0,
            'time_bonus' => 0,
            'leg_number' => (int)($result['Leg'] ?? 1),
            'is_best' => false,
            'result_type' => ['id' => ResultType::STAGE, 'description' => 'Stage'],
        ];
        if ($position !== null) {
            $mapped['position'] = $position;
        }
        if ($startTime) {
            $mapped['start_time'] = $startTime->format(self::DATE_FORMAT);
        }
        if ($finishTime) {
            $mapped['finish_time'] = $finishTime->format(self::DATE_FORMAT);
        }
        if (isset($result['Time'])) {
            $mapped['time_seconds'] = self::numberOf($result['Time']);
        }
        if (isset($result['TimeBehind'])) {
            $mapped['time_behind'] = self::numberOf($result['TimeBehind']);
            // OE only computes TimeBehind for a result it means to include in totalizations
            $mapped['contributory'] = true;
        }
        $mapped = $this->_withScores($mapped, $result['Score'] ?? []);
        $mapped['splits'] = $splits;
        return $mapped;
    }

    private function _withScores(array $mapped, array $scores): array
    {
        foreach (self::listOf($scores) as $score) {
            $value = self::numberOf($score['@'] ?? $score['@value'] ?? 0);
            switch ((string)($score['@type'] ?? '')) {
                case 'Score':
                case 'FinalScore':
                    $mapped['points_final'] = $value;
                    break;
                case 'Penalty':
                case 'PenaltyScore':
                case 'ScorePenalty':
                    $mapped['points_penalty'] = $value * $this->_penaltyFactor;
                    break;
                case 'ManualScoreAdjust':
                    $mapped['points_adjusted'] = $value;
                    break;
                case 'XtraPoints':
                case 'ScoreBonus':
                    $mapped['points_bonus'] = $value;
                    break;
                case 'Time':
                    $mapped['time_seconds'] = $value;
                    break;
                case 'TimePenalty':
                    $mapped['time_penalty'] = $value;
                    break;
                default:
                    break;
            }
        }
        return $mapped;
    }

    /**
     * IOF stores elapsed seconds since the runner started, so the absolute punch time has to be
     * reconstructed. A split flagged Missing keeps its station and order but carries no time at all.
     */
    private function _splits(array $result, array $runner, int $stageOrder, ?DateTimeImmutable $startTime): array
    {
        $splits = [];
        $orderNumber = 1;
        foreach (self::listOf($result['SplitTime'] ?? []) as $splitTime) {
            $status = (string)($splitTime['@status'] ?? '');
            $split = [
                'sicard' => $runner['sicard'],
                'station' => (string)($splitTime['ControlCode'] ?? ''),
                'points' => 0,
                'status' => $status === Split::STATUS_ADDITIONAL ? Split::STATUS_ADDITIONAL : $status,
                'stage_order' => $stageOrder,
            ];
            $elapsed = $splitTime['Time'] ?? null;
            if ($status !== Split::STATUS_MISSING && $elapsed !== null && $startTime) {
                $readingTime = $startTime->modify('+' . (int)round((float)$elapsed * 1000) . ' milliseconds');
                $split['reading_time'] = $readingTime->format(self::DATE_FORMAT);
                $split['reading_milli'] = (int)round((float)$readingTime->format('U.v') * 1000);
                $split['time_seconds'] = self::numberOf($elapsed);
            }
            $split['bib_runner'] = $runner['bib_number'];
            $split['order_number'] = $orderNumber++;
            $splits[] = $split;
        }
        return $splits;
    }

    private function _hasMissingSplit(array $splits): bool
    {
        foreach ($splits as $split) {
            if (($split['status'] ?? '') === Split::STATUS_MISSING) {
                return true;
            }
        }
        return false;
    }

    private function _statusOf(array $result): ?string
    {
        $status = $result['Status'] ?? null;
        return $status === null ? null : (string)$status;
    }

    private function _firstControlCard(array $result): string
    {
        return self::scalarOf(self::listOf($result['ControlCard'] ?? null)[0] ?? null);
    }

    private function _dateOf(mixed $value): ?DateTimeImmutable
    {
        if (!$value || !is_string($value)) {
            return null;
        }
        return new DateTimeImmutable($value, $this->timeZone);
    }

    /**
     * Cake's Xml::toArray() gives a single repeated element as one object and several as a list, so
     * every repeatable node has to be normalised before it is walked. Splits_CEEBO.xml has a class with
     * one PersonResult whose Result has one SplitTime, which is exactly this hazard.
     */
    public static function listOf(mixed $node): array
    {
        if ($node === null || $node === '' || $node === []) {
            return [];
        }
        if (!is_array($node)) {
            return [$node];
        }
        return array_is_list($node) ? $node : [$node];
    }

    /**
     * An element with attributes arrives as an array with the text under '@'; a bare one arrives as the
     * string itself.
     */
    public static function scalarOf(mixed $node): string
    {
        if (is_array($node)) {
            return (string)($node['@'] ?? '');
        }
        return (string)$node;
    }

    private static function firstOf(mixed $node): array
    {
        $list = self::listOf($node);
        $first = $list[0] ?? [];
        return is_array($first) ? $first : [];
    }

    /**
     * The desktop client stringifies these from a Java Double, so 700 arrives as "700.0". Matching that
     * matters beyond looks: UploadHelper::md5Encode() compares scalars as strings, so "700" and "700.0"
     * would hash differently and the same event would look changed depending on which format it came in.
     */
    private static function distanceOf(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $number = (float)$value;
        return $number === floor($number) ? sprintf('%.1f', $number) : (string)$number;
    }

    /**
     * Whole numbers stay integers, as the desktop client does, so 142 does not become 142.0.
     */
    private static function numberOf(mixed $value): int|float
    {
        $number = (float)$value;
        return $number === floor($number) ? (int)$number : $number;
    }
}
