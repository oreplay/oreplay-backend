<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadInterface;
use Results\Lib\UploadHelper;
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

    public function createControlIfNotExists(UploadInterface $helper, array $data): Control
    {
        if (!($data['station'] ?? null)) {
            throw new InternalErrorException('Station number is needed to create control ' . json_encode($data));
        }
        $entity = $helper->getExistingControlByStation($data['station']);
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $helper->getEventId(), $helper->getStageId());
            $helper->storeControlByStation($entity);
        }
        $entity->setAsNew();
        if ($data['station'] === 1 || $data['station'] === '1') {
            $entity->setTypeClearIfNotDefined();
        } else if ($data['station'] > 19 && $data['station'] < 30) {
            $entity->setTypeFinishIfNotDefined();
        } else {
            $entity->setTypeNormalIfNotDefined();
        }
        return $entity;
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId): Control
    {
        /** @var Control $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        return $res;
    }

    public function getAllControls(UploadHelper $helper): ResultSetInterface
    {
        return $this->findWhereEventAndStage($helper)
            ->orderAsc('station')
            ->all();
    }
}
