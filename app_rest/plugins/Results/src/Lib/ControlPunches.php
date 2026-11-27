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
        $runnersBySplitId = $this->_loadRunnersBySplitId($splits);
        $teamIdsBySplitId = $this->_teamIdsBySplitId($splits, $runnersBySplitId);
        $teamsById = $this->_loadTeamsById($teamIdsBySplitId);
        $rows = [];
        foreach ($splits as $split) {
            $teamId = $teamIdsBySplitId[$split->id];
            $rows[] = RestApiEntity::toChild('ControlPunch', [
                'split' => $split,
                'runner' => $runnersBySplitId[$split->id] ?? null,
                'team' => $teamsById[$teamId] ?? null,
            ]);
        }
        return $rows;
    }

    /**
     * @param Split[] $splits
     * @return array<string, Runner>
     */
    private function _loadRunnersBySplitId(array $splits): array
    {
        $runnerIdsBySplitId = $this->_runnerIdsBySplitId($splits);
        $runnerIds = $this->_distinctNonNullIds($runnerIdsBySplitId);
        if (!$runnerIds) {
            return [];
        }
        $runnersById = $this->Runners->findInStageByIds($this->eventId, $this->stageId, $runnerIds)
            ->all()
            ->indexBy('id')
            ->toArray();
        $runnersBySplitId = [];
        foreach ($runnerIdsBySplitId as $splitId => $runnerId) {
            if (isset($runnersById[$runnerId])) {
                $runnersBySplitId[$splitId] = $runnersById[$runnerId];
            }
        }
        return $runnersBySplitId;
    }

    /**
     * @param Split[] $splits
     * @return array<string, ?string>
     */
    private function _runnerIdsBySplitId(array $splits): array
    {
        $resultIds = $this->_distinctNonNullIds(array_map(fn (Split $split) => $split->runner_result_id, $splits));
        $runnerIdsByResultId = $resultIds ? $this->Runners->RunnerResults->runnerIdsByResultId($resultIds) : [];
        $runnerIds = [];
        foreach ($splits as $split) {
            $runnerIds[$split->id] = $runnerIdsByResultId[$split->runner_result_id] ?? $split->runner_id;
        }
        return $runnerIds;
    }

    /**
     * @param Split[] $splits
     * @param array<string, Runner> $runnersBySplitId
     * @return array<string, ?string>
     */
    private function _teamIdsBySplitId(array $splits, array $runnersBySplitId): array
    {
        $teamIdsByResultId = $this->_teamIdsByResultId($splits);
        $teamIds = [];
        foreach ($splits as $split) {
            $runner = $runnersBySplitId[$split->id] ?? null;
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
