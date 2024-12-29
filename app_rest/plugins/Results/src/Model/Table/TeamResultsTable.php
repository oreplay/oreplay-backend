<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property TeamsTable $Teams
 * @property SplitsTable $Splits
 */
class TeamResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamsTable::addBelongsTo($this);
        SplitsTable::addBelongsTo($this)->setSort(['order_number' => 'ASC', 'reading_time' => 'ASC']);
    }
}
