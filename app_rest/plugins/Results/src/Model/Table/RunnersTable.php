<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Model\Entity\Runner;

/**
 * @property UsersTable $Users
 * @property RunnerResultsTable $RunnerResults
 * @property StagesTable $Stages
 * @property ClassesTable $Classes
 * @property ClubsTable $Clubs
 * @property TeamsTable $Teams
 */
class RunnersTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addBelongsTo($this);
        StagesTable::addHasMany($this);
        UsersTable::addHasMany($this);
        ClassesTable::addHasMany($this);
        ClubsTable::addHasMany($this);
        TeamsTable::addHasMany($this);
    }

    private function _matchRunner(string $eventId, string $stageId, array $runnerData): Runner
    {
        $q = $this->_findRunnersInStage($eventId, $stageId);
        if ($runnerData['sicard'] ?? null) {
            $q->where(['sicard' => $runnerData['sicard']]);
        }
        if ($runnerData['bib_number'] ?? null) {
            $q->where(['bib_number' => $runnerData['bib_number']]);
        }
        if ($runnerData['first_name'] ?? null) {
            $q->where(['first_name' => $runnerData['first_name']]);
        }
        if ($runnerData['last_name'] ?? null) {
            $q->where(['last_name' => $runnerData['last_name']]);
        }
        /** @var Runner $potentialRunner */
        $potentialRunner = $q->first();
        if (!$potentialRunner) {
            throw new NotFoundException('Not found runner');
        }
        return $potentialRunner;
    }

    public function createRunnerIfNotExists(string $eventId, string $stageId, array $runnerData): Runner
    {
        try {
            $runner = $this->_matchRunner($eventId, $stageId, $runnerData);
        } catch (NotFoundException $e) {
            /** @var Runner $runner */
            $runner = $this->patchFromNewWithUuid($runnerData);
        }
        $runner->event_id = $eventId;
        $runner->stage_id = $stageId;
        return $runner;
    }

    private function _findRunnersInStage(string $eventId, string $stageId): RestApiSelectQuery
    {
        /** @var RestApiSelectQuery $res */
        $res = $this->find()
            ->where(['Runners.event_id' => $eventId, 'Runners.stage_id' => $stageId]);
        return $res;
    }

    public function findRunnersInStage(string $eventId, string $stageId, array $filters = []): Query
    {
        $q = $this->_findRunnersInStage($eventId, $stageId);
        if ($filters['class_id'] ?? null) {
            $q->where(['class_id' => $filters['class_id']]);
        }
        return $q->contain(ClubsTable::name())
            ->contain(ClassesTable::name())
            ->contain(
                RunnerResultsTable::name()
                . '.' . SplitsTable::name()
                . '.' . ControlsTable::name()
                . '.' . ControlTypesTable::name()
            );

    }
}
