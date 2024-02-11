<?php
declare(strict_types=1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;

/**
 * @property RunnersTable $Runners
 * @property SplitsTable $Splits
 */
class TeamResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamsTable::addBelongsTo($this);
        SplitsTable::addHasMany($this);
    }
}
