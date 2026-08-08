<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadContext;
use Results\Model\Entity\TeamResult;

/**
 * @property ResultTypesTable $ResultTypes
 * @property TeamsTable $Teams
 * @property SplitsTable $Splits
 */
class TeamResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamsTable::addBelongsTo($this);
        SplitsTable::addBelongsTo($this)->setSort(SplitsTable::defaultOrder());
        ResultTypesTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var TeamResultsTable $table */
        $table = parent::load();
        return $table;
    }

    protected function _insert(EntityInterface $entity, array $data): EntityInterface|false
    {
        return parent::_insert($entity, $data);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId): TeamResult
    {
        /** @var TeamResult $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        return $res;
    }

    public function getAllResults(UploadContext $context): ResultSetInterface
    {
        return $this->findWhereEventAndStage($context)
            ->orderByAsc('team_id')
            ->all();
    }
}
