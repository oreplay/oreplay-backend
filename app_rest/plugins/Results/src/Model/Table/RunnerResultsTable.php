<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Helper\HashHelper;
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

    private const RUNNER_TIME = 'runnerTime';
    private const RUNNER_TIME_1 = 'runnerTime2';
    private const RUNNER_TIME_2 = 'runnerTime3';
    private const RUNNER_TIME3 = 'runnerTime4';

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

    public function getRunnerTime(): float
    {
        return $this->getTime(self::RUNNER_TIME);
    }

    public function getRunnerTime1(): float
    {
        return $this->getTime(self::RUNNER_TIME_1);
    }

    public function getRunnerTime2(): float
    {
        return $this->getTime(self::RUNNER_TIME_2);
    }

    public function getRunnerTime3(): float
    {
        return $this->getTime(self::RUNNER_TIME3);
    }

    private function _newResultWithType(array $resultData, UploadHelper $helper): RunnerResult
    {
        $runnerResultToSave = $this->fillNewWithStage($resultData, $helper->getEventId(), $helper->getStageId());
        $runnerResultToSave->setHash($resultData);

        $runnerResultToSave->result_type = $this
            ->ResultTypes
            ->getCachedWithDefault($helper->getChecker(), $resultData['result_type']['id'] ?? null);

        return $runnerResultToSave;
    }

    public function createRunnerResult(array $resultData, Runner $runner, UploadHelper $helper): Runner
    {
        $this->startTimer(self::RUNNER_TIME);
        $this->startTimer(self::RUNNER_TIME_1);

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
        $this->endTimer(self::RUNNER_TIME);

        $splits = $resultData['splits'] ?? [];
        $this->startTimer(self::RUNNER_TIME3);
        if ($splits) {
            $this->Splits->deleteAllByRunnerId($runnerResultToSave->id);
            $this->startTimer(self::RUNNER_TIME_2);
            $runnerResultToSave = $this->Splits->uploadForEachSplit($runnerResultToSave, $splits, $helper);
            $this->endTimer(self::RUNNER_TIME_2);
        }
        $this->endTimer(self::RUNNER_TIME3);
        // add the new runner result to the runner
        $res = $runner->addRunnerResult($runnerResultToSave);
        $this->endTimer(self::RUNNER_TIME_1);
        return $res;
    }
}
