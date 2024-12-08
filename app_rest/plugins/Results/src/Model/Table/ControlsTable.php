<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Control;

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

    protected function getByStation(string $eventId, string $stageId, string $station): ?Control
    {
        $conditions = [
            $this->_alias . '.event_id' => $eventId,
            $this->_alias . '.stage_id' => $stageId,
            $this->_alias . '.station' => $station
        ];
        $cacheKey = 'getByStation_' . $this->_alias . '_' . md5(json_encode($conditions));
        return $this->getFromCache([$cacheKey, $conditions]);
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Control
    {
        if (!($data['station'] ?? null)) {
            throw new InternalErrorException('Station number is needed to create control ' . json_encode($data));
        }
        $entity = $this->getByStation($eventId, $stageId, $data['station']);
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $eventId, $stageId);
        }
        $entity->setTypeNormalIfNotDefined();
        return $entity;
    }
}
