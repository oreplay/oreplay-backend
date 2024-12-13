<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Traits\TimingTrait;

/**
 * @property ResultTypesTable $ResultTypes
 * @property RunnersTable $Runners
 * @property SplitsTable $Splits
 */
class RunnerResultsTable extends AppTable
{
    use TimingTrait;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addHasMany($this);
        SplitsTable::addBelongsTo($this);
        ResultTypesTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var RunnerResultsTable $table */
        $table = parent::load();
        return $table;
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId): RunnerResult
    {
        /** @var RunnerResult $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        return $res;
    }

    public function hasFinishTimes(string $eventId, string $stageId): bool
    {
        $res = $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
            'finish_time is not null'
        ])->first();
        return !!$res;
    }

    public function getAllResults(UploadHelper $helper): ResultSetInterface
    {
        return $this->findWhereEventAndStage($helper)
            ->orderAsc('runner_id')
            ->all();
    }

    private function _newResultWithType(array $resultData, UploadHelper $helper): RunnerResult
    {
        $runnerResultToSave = $this->fillNewWithStage($resultData, $helper->getEventId(), $helper->getStageId());

        $runnerResultToSave->result_type = $this
            ->ResultTypes
            ->getCachedWithDefault($helper->getChecker(), $resultData['result_type']['id'] ?? null);

        return $runnerResultToSave;
    }

    public function createRunnerResult(array $resultData, Runner $runner, UploadHelper $helper): Runner
    {
        $helper->getMetrics()->startRunnerResultsTime();
        $runnerResultToSave = $this->_newResultWithType($resultData, $helper);

        $existingRunnerResults = $helper->getExistingDbResultsForThisRunner($runner, $runnerResultToSave);
        $existingRunnerResultsAmount = count($existingRunnerResults);
        if ($existingRunnerResultsAmount) {
            if ($existingRunnerResultsAmount === 1) {
                // if there is only one existing result, we reuse the ID to replace the db row
                $runnerResultToSave->setIdToUpdate($existingRunnerResults[0]->id);
            } else {
                // if there is more than one result, we keep them all in the runner
                foreach ($existingRunnerResults as $existingResult) {
                    $runner = $runner->addRunnerResult($existingResult);
                }
            }
        }
        $helper->getMetrics()->endRunnerResultsTime();

        $helper->getMetrics()->startSplitsTime();
        $splits = $resultData['splits'] ?? [];
        if ($splits && !$runnerResultToSave->hasSameSplits($splits)) {
            $runnerResultToSave->setHash($splits);
            $this->Splits->deleteAllByRunnerId($runnerResultToSave->id);
            $runnerResultToSave = $this->Splits->uploadForEachSplit($runnerResultToSave, $splits, $helper);
        }
        // add the new runner result to the runner
        $res = $runner->addRunnerResult($runnerResultToSave);
        $helper->getMetrics()->endSplitsTime();
        return $res;
    }
}
