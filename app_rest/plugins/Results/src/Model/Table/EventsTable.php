<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Results\Model\Entity\Event;

/**
 * @property FederationsTable $Federations
 * @property StagesTable $Stages
 */
class EventsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        FederationsTable::addHasMany($this);
        StagesTable::addBelongsTo($this);
    }

    public function findPaginatedEvents(array $filters): Query
    {
        return $this->find()->orderAsc('created');
    }

    public function getEventWithRelations(string $id): Event
    {
        $query = $this->find()
            ->contain(FederationsTable::name())
            ->contain(StagesTable::name())
            ->where(['Events.id' => $id]);
        /** @var Event $res */
        $res = $query->firstOrFail();
        return $res;
    }
}
