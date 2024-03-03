<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property ControlsTable $Controls
 */
class ControlTypesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        ControlsTable::addBelongsTo($this);
    }
}
