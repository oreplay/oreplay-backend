<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;

/**
 * @property RunnersTable $Runner
 * @property ClassesTable $Classes
 * @property ClubsTable $Clubs
 * @property TeamResultsTable $TeamResults
 */
class TeamsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamResultsTable::addBelongsTo($this);
        RunnersTable::addBelongsTo($this);
        ClassesTable::addHasMany($this);
        ClubsTable::addHasMany($this);
    }

    private function _findTeamsInStage(string $eventId, string $stageId): RestApiSelectQuery
    {
        /** @var RestApiSelectQuery $res */
        $res = $this->find()
            ->where([$this->_alias . '.event_id' => $eventId, $this->_alias . '.stage_id' => $stageId]);
        return $res;
    }

    public function findTeamsInStage(string $eventId, string $stageId, array $filters = []): Query
    {
        $q = $this->_findTeamsInStage($eventId, $stageId);
        if ($filters['class_id'] ?? null) {
            $q->where(['class_id' => $filters['class_id']]);
        }
        return $q->contain(ClubsTable::name())
            ->contain(ClassesTable::name())
            ->contain(
                TeamResultsTable::name()
                . '.' . SplitsTable::name()
                . '.' . ControlsTable::name()
                . '.' . ControlTypesTable::name()
            );
    }
}
