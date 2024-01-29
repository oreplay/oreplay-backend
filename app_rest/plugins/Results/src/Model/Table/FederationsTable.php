<?php

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Results\Model\Entity\Event;

/**
 * @property EventsTable $Events
 */
class FederationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior('Timestamp');
        $this->hasMany('Events',
            ['className' => EventsTable::nameWithPlugin()]);
    }

    public function getEventWith($id): Event
    {
        $query = $this->find()->contain('Federations')->where(['id' => $id]);
        /** @var Event $res */
        $res = $query->firstOrFail();
        return $res;
    }
}
