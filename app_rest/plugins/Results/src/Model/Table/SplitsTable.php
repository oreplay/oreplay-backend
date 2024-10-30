<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;

/**
 * @property RunnerResultsTable $RunnerResults
 * @property TeamResultsTable $TeamResults
 * @property ControlsTable $ControlsTable
 */
class SplitsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addHasMany($this);
        TeamResultsTable::addHasMany($this);
        ControlsTable::addHasMany($this);
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Split
    {
        $conditions = $data;
        unset($conditions['time_seconds']);

        $cacheKey = 'createIfNotExists_' . $this->_alias . '_' . md5(json_encode($conditions));
        /** @var Split $entity */
        $entity = $this->getFromCache([$cacheKey, $conditions]);
        if (!$entity) {
            $entity = $this->patchNewWithStage($data, $eventId, $stageId);
        }
        return $entity;
    }

    public function deleteAllByRunnerId(string $runnerId): int
    {
        return $this->deleteAll(['runner_id' => $runnerId]);
    }

    public function uploadSplits(RunnerResult $resultToSave, array $splits, UploadHelper $helper): RunnerResult
    {
        if ($splits) {
            $this->deleteAllByRunnerId($resultToSave->id);
            foreach ($splits as $split) {
                $splitToSave = $this->createIfNotExists($helper->getEventId(), $helper->getStageId(), $split);
                $resultToSave->addSplit($splitToSave);
            }
        }
        return $resultToSave;
    }
}
