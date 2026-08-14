<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\ExistingResultsIndex;
use Results\Lib\UploadContext;
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

    public function createControlIfNotExists(
        UploadContext $context,
        ExistingResultsIndex $existingResults,
        array $data
    ): Control {
        if (!($data['station'] ?? null)) {
            throw new InternalErrorException('Station number is needed to create control ' . json_encode($data));
        }
        $entity = $existingResults->getExistingControlByStation($data['station']);
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $context->getEventId(), $context->getStageId());
            $existingResults->storeControlByStation($entity);
        }
        $entity->setAsNew();
        $entity->is_intermediate = $this->_stillIntermediateAfter($entity, $data);
        if ($data['station'] === 1 || $data['station'] === '1') {
            $entity->setTypeClearIfNotDefined();
        } else if ($data['station'] > 19 && $data['station'] < 30) {
            $entity->setTypeFinishIfNotDefined();
        } else {
            $entity->setTypeNormalIfNotDefined();
        }
        return $entity;
    }

    // a station that has ever been read by a radio keeps the flag: the later download that replaces
    // the punch is not evidence that the radio was removed
    private function _stillIntermediateAfter(Control $entity, array $data): bool
    {
        return ($entity->is_intermediate ?? false) || (bool)($data['is_intermediate'] ?? false);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId): Control
    {
        /** @var Control $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        return $res;
    }

    /**
     * @return Control[] keyed by station, holding only the fields the radio list exposes
     */
    public function intermediateInStage(string $stageId): array
    {
        $controls = $this->find()
            ->select(['id', 'station'])
            ->where([
                ControlsTable::field('stage_id') => $stageId,
                ControlsTable::field('is_intermediate') => true,
                ControlsTable::field('deleted') . ' IS' => null,
            ])
            ->all();
        $byStation = [];
        /** @var Control $control */
        foreach ($controls as $control) {
            $byStation[(string)$control->station] = $control;
        }
        return $byStation;
    }

    /**
     * Controls already stored are never rewritten by the upload, so the flag set on the entity
     * would be lost for every station but the ones created by this upload.
     */
    public function markIntermediateStations(string $stageId, array $stations): int
    {
        if (!$stations) {
            return 0;
        }
        return $this->updateAll(
            ['is_intermediate' => true],
            ['stage_id' => $stageId, 'station IN' => $stations, 'is_intermediate' => false]
        );
    }

    public function getAllControls(UploadContext $context): ResultSetInterface
    {
        return $this->findWhereEventAndStage($context)
            ->orderByAsc('station')
            ->all();
    }
}
