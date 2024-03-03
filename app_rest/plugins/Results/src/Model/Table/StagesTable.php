<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property EventsTable $Events
 * @property RunnersTable $Runners
 */
class StagesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addHasMany($this);
        RunnersTable::addBelongsTo($this);
    }
}
