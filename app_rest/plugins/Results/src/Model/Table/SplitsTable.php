<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;

/**
 * @property RunnerResultsTable $RunnerResults
 * @property TeamResultsTable $TeamResults
 * @property ControlsTable $Controls
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

    protected function _insert(EntityInterface $entity, array $data)
    {
        return parent::_insert($entity, $data);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId)
    {
        /** @var Split $split */
        $split = parent::fillNewWithStage($data, $eventId, $stageId);
        return $split;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Split
    {
        $conditions = $data;
        unset($conditions['time_seconds']);

        $cacheKey = 'createIfNotExists_' . $this->_alias . '_' . UploadHelper::md5Encode($conditions);
        /** @var Split $entity */
        $entity = $this->getFromCache([$cacheKey, $conditions]);
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $eventId, $stageId);
        }
        return $entity;
    }

    public function deleteAllByRunnerId(string $runnerId): int
    {
        return $this->deleteAll(['runner_id' => $runnerId]);
    }

    public function uploadForEachSplit(RunnerResult $resultToSave, array $splits, UploadHelper $helper): RunnerResult
    {
        if ($splits) {
            foreach ($splits as $split) {
                $split['is_intermediate'] = $helper->getChecker()->isIntermediates();
                $splitToSave = $this->fillNewWithStage($split, $helper->getEventId(), $helper->getStageId());
                if ($split['station'] ?? null) {
                    $control = $this->Controls->createControlIfNotExists($helper, $split);
                    $splitToSave->addControl($control);
                }
                $helper->getMetrics()->addOneSplit();
                $resultToSave->addSplit($splitToSave);
            }
        }
        return $resultToSave;
    }

    public function uploadAllSplits($splits, RunnerResult $runnerResultToSave, UploadHelper $helper): RunnerResult
    {
        $helper->getMetrics()->startSplitsTime();
        if ($splits && !$runnerResultToSave->hasSameSplits($splits)) {
            $runnerResultToSave->setHash($splits);
            if (!$helper->getChecker()->isIntermediates()) {
                $this->deleteAllByRunnerId($runnerResultToSave->id);
            }
            $runnerResultToSave = $this->uploadForEachSplit($runnerResultToSave, $splits, $helper);
        }
        $helper->getMetrics()->endSplitsTime();
        return $runnerResultToSave;
    }
}
