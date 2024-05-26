<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Stage;

/**
 * @property EventsTable $Events
 * @property RunnersTable $Runners
 * @property StageTypesTable $StageTypes
 */
class StagesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addHasMany($this);
        RunnersTable::addBelongsTo($this);
        StageTypesTable::addHasMany($this);
    }

    public function getByEvent(string $id, string $eventId): Stage
    {
        /** @var Stage $res */
        $res = $this->find()
            ->where([$this->_alias . '.id' => $id, 'event_id' => $eventId])
            ->contain('StageTypes')
            ->firstOrFail();
        return $res;
    }
}
