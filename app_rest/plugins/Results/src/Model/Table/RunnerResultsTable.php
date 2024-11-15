<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;

/**
 * @property ResultTypesTable $ResultTypes
 * @property RunnersTable $Runners
 * @property SplitsTable $Splits
 */
class RunnerResultsTable extends AppTable
{
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
        return $this->findWhereEventAndStage($helper)->all();
    }

    public function createRunnerResult(array $resultData, Runner $runner, UploadHelper $helper): Runner
    {
        /** @var RunnerResult $runnerResultToSave */
        $runnerResultToSave = $this->patchNewWithStage($resultData, $helper->getEventId(), $helper->getStageId());
        $runnerResultToSave->result_type = $this
            ->ResultTypes
            ->getCachedWithDefault($helper->getChecker(), $resultData['result_type']['id'] ?? null);

        $existingRunnerResults = $helper->getExistingResultsForThisRunner($runner, $runnerResultToSave);
        if ($existingRunnerResults->count() === 1) {
            $runnerResultToSave->id = $existingRunnerResults->first()->id;
        } else {
            foreach ($existingRunnerResults as $existingResult) {
                $runner = $runner->addRunnerResult($existingResult);
            }
        }

        $splits = $resultData['splits'] ?? [];
        if ($splits) {
            $runnerResultToSave = $this->Splits->uploadForEachSplit($runnerResultToSave, $splits, $helper);
        }
        return $runner->addRunnerResult($runnerResultToSave);
    }
}
