<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
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

    public static function load(): self
    {
        /** @var StagesTable $table */
        $table = parent::load();
        return $table;
    }

    public function findByEvent(string $stageId, string $eventId): Query
    {
        return $this->find()
            ->where([$this->_alias . '.id' => $stageId, 'event_id' => $eventId])
            ->contain(StageTypesTable::name());
    }

    public function getByEvent(string $stageId, string $eventId): Stage
    {
        /** @var Stage $res */
        $res = $this->findByEvent($stageId, $eventId)
            ->firstOrFail();
        return $res;
    }
}
