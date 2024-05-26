<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property ResultTypesTable $ResultTypes
 * @property RunnersTable $Runners
 * @property SplitsTable $Splits
 */
class RunnerResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addHasMany($this);
        SplitsTable::addBelongsTo($this);
        ResultTypesTable::addHasMany($this);
    }

    public function hasFinishTimes(string $eventId, string $stageId): bool
    {
        $res = $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
            'finish_time is not null'
        ])->first();
        return !!$res;
    }
}
