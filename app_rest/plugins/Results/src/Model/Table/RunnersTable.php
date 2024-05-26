<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Model\Entity\ClassEntity;
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

    private function _findRunnersInStageBy(
        string $field,
        string $eventId,
        string $stageId,
        array $runnerData
    ): ?Runner {
        /** @var Runner $potentialRunner */
        $q = $this->_findRunnersInStage($eventId, $stageId);
        if ($runnerData[$field] ?? null) {
            $q->where([$field => $runnerData[$field]]);
            $potentialRunner = $q->first();
            if ($potentialRunner) {
                return $potentialRunner;
            } else {
                throw new NotFoundException('Not found runner by ' . $field);
            }
        }
        return null;
    }

    public function matchRunner(
        string $eventId,
        string $stageId,
        array $runnerData,
        ClassEntity $class = null
    ): Runner {
        $runner = $this->_findRunnersInStageBy('db_id', $eventId, $stageId, $runnerData);
        if ($runner) {
            return $runner;
        }
        $runner = $this->_findRunnersInStageBy('bib_number', $eventId, $stageId, $runnerData);
        if ($runner) {
            return $runner;
        }
        /** @var Runner $potentialRunner */
        $q = $this->_findRunnersInStage($eventId, $stageId);
        $sicard = $runnerData['sicard'] ?? null;
        $stName = $runnerData['first_name'] ?? null;
        $lastName = $runnerData['last_name'] ?? null;
        if ($sicard && $stName && $lastName) {
            $q->where([
                'sicard' => $runnerData['sicard'],
                'first_name' => $runnerData['first_name'],
                'last_name' => $runnerData['last_name']
            ]);
            if ($class) {
                $q->where(['class_id' => $class->id]);
            }
            $potentialRunner = $q->first();
            if ($potentialRunner) {
                return $potentialRunner;
            } else {
                $q = $this->_findRunnersInStage($eventId, $stageId);
                $q->where([
                    'first_name' => $runnerData['first_name'],
                    'last_name' => $runnerData['last_name']
                ]);
                $err = '';
                if ($class) {
                    $err = ' in class';
                    $q->where(['class_id' => $class->id]);
                }
                /** @var Runner $potentialRunner */
                $potentialRunner = $q->first();
                if ($potentialRunner) {
                    return $potentialRunner;
                } else {
                    throw new NotFoundException('Not found runner by name' . $err);
                }
            }
        }
        throw new NotFoundException('Runner not found');
    }

    public function createRunnerIfNotExists(
        string $eventId,
        string $stageId,
        array $runnerData,
        ClassEntity $class = null
    ): Runner {
        try {
            $runner = $this->matchRunner($eventId, $stageId, $runnerData, $class);
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
