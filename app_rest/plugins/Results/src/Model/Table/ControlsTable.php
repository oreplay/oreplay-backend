<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property SplitsTable $Splits
 * @property ControlTypesTable $ControlTypes
 */
class ControlsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        SplitsTable::addBelongsTo($this);
        ControlTypesTable::addHasMany($this);
    }
}
