<?php

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property RunnerResultsTable $RunnerResults
 */
class RunnersTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addHasMany($this);
    }

    public function findRunnersInStage(string $eventId, string $stageId)
    {
        return $this->find()
            ->where(['event_id' => $eventId, 'stage_id' => $stageId])
            ->contain(RunnerResultsTable::name().'.'.SplitsTable::name());

    }
}
