<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Event;

/**
 * @property EventsTable $Events
 */
class FederationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addBelongsTo($this);
    }
}
