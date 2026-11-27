<?php

declare(strict_types = 1);

namespace Results\Lib;

use RestApi\Model\Entity\RestApiEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Split;
use Results\Model\Entity\Team;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamsTable;

class ControlPunches
{
    public function __construct(
        private readonly RunnersTable $Runners,
        private readonly TeamsTable $Teams,
        private readonly string $eventId,
        private readonly string $stageId,
    ) {
    }

    /**
     * @param Split[] $splits
     * @return array<int, array{split: Split, runner: ?Runner, team: ?Team}>
     */
    public function buildRows(array $splits): array
    {
        $runnersByResultId = $this->_loadRunnersByResultId($splits);
        $teamIdsBySplitId = $this->_teamIdsBySplitId($splits, $runnersByResultId);
        $teamsById = $this->_loadTeamsById($teamIdsBySplitId);
        $rows = [];
        foreach ($splits as $split) {
            $teamId = $teamIdsBySplitId[$split->id];
            $rows[] = RestApiEntity::toChild('ControlPunch', [
                'split' => $split,
                'runner' => $runnersByResultId[$split->runner_result_id] ?? null,
                'team' => $teamsById[$teamId] ?? null,
            ]);
        }
        return $rows;
    }

    /**
     * @param Split[] $splits
     * @return array<string, Runner>
     */
    private function _loadRunnersByResultId(array $splits): array
    {
        $resultIds = $this->_distinctNonNullIds(array_map(fn (Split $split) => $split->runner_result_id, $splits));
        if (!$resultIds) {
            return [];
        }
        $runnerIdsByResultId = $this->Runners->RunnerResults->runnerIdsByResultId($resultIds);
        $runnersById = $this->Runners
            ->findInStageByIds($this->eventId, $this->stageId, $this->_distinctNonNullIds($runnerIdsByResultId))
            ->all()
            ->indexBy('id')
            ->toArray();
        $runnersByResultId = [];
        foreach ($runnerIdsByResultId as $resultId => $runnerId) {
            if (isset($runnersById[$runnerId])) {
                $runnersByResultId[$resultId] = $runnersById[$runnerId];
            }
        }
        return $runnersByResultId;
    }

    /**
     * @param Split[] $splits
     * @param array<string, Runner> $runnersByResultId
     * @return array<string, ?string>
     */
    private function _teamIdsBySplitId(array $splits, array $runnersByResultId): array
    {
        $teamIdsByResultId = $this->_teamIdsByResultId($splits);
        $teamIds = [];
        foreach ($splits as $split) {
            $runner = $runnersByResultId[$split->runner_result_id] ?? null;
            $teamIds[$split->id] = $teamIdsByResultId[$split->team_result_id] ?? $runner?->team_id;
        }
        return $teamIds;
    }

    /**
     * @param Split[] $splits
     * @return array<string, string>
     */
    private function _teamIdsByResultId(array $splits): array
    {
        $resultIds = $this->_distinctNonNullIds(array_map(fn (Split $split) => $split->team_result_id, $splits));
        if (!$resultIds) {
            return [];
        }
        return $this->Teams->TeamResults->teamIdsByResultId($resultIds);
    }

    /**
     * @param array<string, ?string> $teamIdsBySplitId
     * @return array<string, Team>
     */
    private function _loadTeamsById(array $teamIdsBySplitId): array
    {
        $teamIds = $this->_distinctNonNullIds($teamIdsBySplitId);
        if (!$teamIds) {
            return [];
        }
        return $this->Teams->findInStageByIds($this->eventId, $this->stageId, $teamIds)
            ->all()
            ->indexBy('id')
            ->toArray();
    }

    /**
     * @param array<?string> $ids
     * @return string[]
     */
    private function _distinctNonNullIds(array $ids): array
    {
        return array_values(array_unique(array_filter($ids)));
    }
}
