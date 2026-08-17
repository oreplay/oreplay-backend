<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use DateTimeImmutable;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Split;

/**
 * One IOF ClassResult as the classes[] array the importers already consume. A port of
 * ConverterIofToModel.convertResultListSingleStageClassic() from the Java desktop client; the field
 * table it follows, and the places it deliberately differs, are in docs/upload-xml-input.md 2B.
 */
class ResultListMapper extends IofClassMapper
{
    private const PENALTY_FIELD = 'points_penalty';

    /**
     * IOF puts every point and penalty in a Score element distinguished only by @type, and the producers
     * disagree on which name they use for the same thing.
     */
    private const RESULT_FIELD_OF_SCORE_TYPE = [
        'Score' => 'points_final',
        'FinalScore' => 'points_final',
        'Penalty' => self::PENALTY_FIELD,
        'PenaltyScore' => self::PENALTY_FIELD,
        'ScorePenalty' => self::PENALTY_FIELD,
        'ManualScoreAdjust' => 'points_adjusted',
        'XtraPoints' => 'points_bonus',
        'ScoreBonus' => 'points_bonus',
        'Time' => 'time_seconds',
        'TimePenalty' => 'time_penalty',
    ];

    protected function personElement(): string
    {
        return 'PersonResult';
    }

    protected function resultElement(): string
    {
        return 'Result';
    }

    /**
     * The same team may be split across several TeamResult tags — one per leg in some exports — so they
     * are merged on the first identifier that is present. A team member's Result is shaped exactly like
     * an individual PersonResult, so the entry mapping is reused unchanged.
     */
    protected function teamsOf(array $data): array
    {
        $teams = [];
        foreach (IofNode::listOf($data['TeamResult'] ?? []) as $teamResult) {
            $key = $this->_teamKeyOf($teamResult);
            $team = $teams[$key] ?? $this->_newTeam($teamResult);
            foreach (IofNode::listOf($teamResult['TeamMemberResult'] ?? []) as $member) {
                $team['runners'][] = $this->_teamMemberOf($member);
                $team['team_results'][] = $this->_teamResultOf($member);
            }
            $teams[$key] = $team;
        }
        return array_values($teams);
    }

    private function _teamKeyOf(array $teamResult): string
    {
        $bib = (string)($teamResult['BibNumber'] ?? '');
        $entryId = IofNode::repeatedTextOf($teamResult['EntryId'] ?? null, 0);
        $name = (string)($teamResult['Name'] ?? '');
        return $bib ?: ($entryId ?: $name);
    }

    private function _newTeam(array $teamResult): array
    {
        $team = [
            'id' => '',
            'uuid' => '',
            'bib_number' => (string)($teamResult['BibNumber'] ?? ''),
            'team_name' => (string)($teamResult['Name'] ?? ''),
            'runners' => [],
            'team_results' => [],
        ];
        $club = $this->clubOf($teamResult['Organisation'] ?? []);
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
        $course = $this->courseOf(IofNode::listOf($member['Result'] ?? [])[0]['Course'] ?? []);
        if ($course) {
            $runner['course'] = $course;
        }
        return $runner;
    }

    /**
     * OverallResult is the team's cumulative standing after that leg, so it gives one team_result per leg.
     * Where it is absent the leg's own result stands in, as the desktop client does.
     */
    private function _teamResultOf(array $member): array
    {
        $legResult = IofNode::listOf($member['Result'] ?? [])[0] ?? [];
        $overall = IofNode::firstOf($legResult['OverallResult'] ?? []);
        $legNumber = (int)($legResult['Leg'] ?? 1);
        if (!$overall) {
            $overall = $legResult;
        }
        $overall['Leg'] = $legNumber;
        $overall['@raceNumber'] = self::stageOrderOf($legResult);
        $teamResult = $this->resultOf($overall, ['sicard' => '', 'bib_number' => '']);
        unset($teamResult['splits']);
        return $teamResult;
    }

    protected function resultOf(array $result, array $runner): array
    {
        $stageOrder = self::stageOrderOf($result);
        $startTime = $this->dateOf($result['StartTime'] ?? null);
        $finishTime = $this->dateOf($result['FinishTime'] ?? null);
        $positionText = IofNode::repeatedTextOf($result['Position'] ?? null, 0);
        $position = $positionText === '' ? null : (int)$positionText;
        $splits = $this->_splits($result, $runner, $stageOrder, $startTime);
        $mapped = [
            'id' => '',
            'stage_order' => $stageOrder,
            'status_code' => NcStatusReconstructor::codeOf(
                self::statusOf($result),
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
            $mapped['time_seconds'] = ClientNumberFormat::number($result['Time']);
        }
        // in a team member's result TimeBehind carries @type="Leg", so it arrives as an array where the
        // individual path gets a plain string; read as a number unwrapped it would be 1 for everyone
        $timeBehind = IofNode::repeatedTextOf($result['TimeBehind'] ?? null, 0);
        if ($timeBehind !== '') {
            $mapped['time_behind'] = ClientNumberFormat::number($timeBehind);
            // OE only computes TimeBehind for a result it means to include in totalizations
            $mapped['contributory'] = true;
        }
        $mapped = $this->_withScores($mapped, $result['Score'] ?? []);
        $mapped['splits'] = $splits;
        return $mapped;
    }

    private function _withScores(array $mapped, array $scores): array
    {
        foreach (IofNode::listOf($scores) as $score) {
            $field = self::RESULT_FIELD_OF_SCORE_TYPE[(string)($score['@type'] ?? '')] ?? null;
            if (!$field) {
                continue;
            }
            $value = ClientNumberFormat::number($score['@'] ?? $score['@value'] ?? 0);
            $mapped[$field] = $field === self::PENALTY_FIELD ? $value * $this->_penaltyFactor : $value;
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
        foreach (IofNode::listOf($result['SplitTime'] ?? []) as $splitTime) {
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
                $split['time_seconds'] = ClientNumberFormat::number($elapsed);
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
}
