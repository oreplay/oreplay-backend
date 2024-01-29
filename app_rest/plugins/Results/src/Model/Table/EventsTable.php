<?php

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Results\Model\Entity\Event;

/**
 * @property FederationsTable $Federations
 * @property StagesTable $Stages
 */
class EventsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior('Timestamp');
        $this->belongsTo('Federations',
            ['className' => FederationsTable::nameWithPlugin()]);
    }

    public function getEventWithRelations($id): Event
    {
        $query = $this->find()
            ->contain('Federations')
            ->where(['Events.id' => $id]);
        /** @var Event $res */
        $res = $query->firstOrFail();
        return $res;
    }
}
