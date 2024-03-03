<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;

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

    public function findRunnersInStage(string $eventId, string $stageId, array $filters = []): Query
    {
        $q = $this->find()
            ->where(['Runners.event_id' => $eventId, 'Runners.stage_id' => $stageId]);
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
