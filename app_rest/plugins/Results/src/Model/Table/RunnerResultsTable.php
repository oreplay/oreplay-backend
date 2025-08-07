<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\ResultType;
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
        SplitsTable::addBelongsTo($this)->setSort(SplitsTable::defaultOrder());
        ResultTypesTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var RunnerResultsTable $table */
        $table = parent::load();
        return $table;
    }

    public function getNotClassesStats(string $eventId, string $stageId, array $classNames, string $sex): array
    {
        $classCondition = [ClassesTable::field('short_name') . ' not in' => $classNames];
        return $this->_getClassStats($classCondition, $eventId, $stageId, $sex);
    }

    public function getClassesStats(string $eventId, string $stageId, array $classNames, string $sex): array
    {
        $classCondition = [ClassesTable::field('short_name') . ' in' => $classNames];
        return $this->_getClassStats($classCondition, $eventId, $stageId, $sex);
    }

    public function _getClassStats(array $classCondition, string $eventId, string $stageId, string $sex = null): array
    {
        $query = $this->find()
            ->where([
                RunnerResultsTable::field('event_id') => $eventId,
                RunnerResultsTable::field('stage_id') => $stageId,
            ])
            ->matching(RunnersTable::name() . '.' . ClassesTable::name(), function ($q) use ($classCondition) {
                return $q->where($classCondition);
            })
            ->order([RunnerResultsTable::field('runner_id') => 'ASC']);
        if ($sex) {
            $query->matching(RunnersTable::name(), function ($q) use ($sex) {
                return $q->where([RunnersTable::field('sex') => $sex]);
            });
        }
        $results = $query->toArray();

        $classes = [];
        $previousRunnerId = '';
        $total = 0;
        $dns = 0;
        $mp = 0;
        $dnf = 0;
        $ot = 0;
        $dqf = 0;
        $notYetFinished = 0;
        $finished = 0;
        $others = 0;
        $otherValues = [];
        /** @var RunnerResult $res */
        foreach ($results as $res) {
            if ($res->runner_id !== $previousRunnerId) {
                $previousRunnerId = $res->runner_id;
                $total++;
                $classes[$res->getMatchingClass()->short_name] = null;
                if ($res->isDNS()) {
                    $dns++;
                } else if ($res->isMP()) {
                    $mp++;
                } else if ($res->isDNF()) {
                    $dnf++;
                } else if ($res->isOT()) {
                    $ot++;
                } else if ($res->isDQF()) {
                    $dqf++;
                } else if ($res->isNotYetFinished()) {
                    $notYetFinished++;
                } else if ($res->isFinished()) {
                    $finished++;
                } else {
                    $others++;
                    $otherValues[$res->status_code] = null;
                }
            }
        }
        return [
            'classes' => array_keys($classes),
            'total' => $total,
            'dns' => $dns,
            'mp' => $mp,
            'dnf' => $dnf,
            'ot' => $ot,
            'dqf' => $dqf,
            'notYetFinished' => $notYetFinished,
            'finished' => $finished,
            'others' => $others,
            'otherValues' => array_keys($otherValues),
        ];
    }

    protected function _insert(EntityInterface $entity, array $data)
    {
        return parent::_insert($entity, $data);
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
        return (bool)$res;
    }

    public function getAllResults(UploadHelper $helper): ResultSetInterface
    {
        return $this->findWhereEventAndStage($helper)
            ->orderAsc('runner_id')
            ->all();
    }

    private function _newResultWithType(array $resultData, UploadHelper $helper): RunnerResult
    {
        if ($helper->getChecker()->isTotals()) {
            $resultType = $resultData['result_type']['id'] ?? null;
            if ($resultType === ResultType::STAGE) {
                $helper->getMetrics()->setWarning('Result type STAGE converted to PARTIAL_OVERALL');
                $resultData['result_type'] = ['id' => ResultType::PARTIAL_OVERALL];
            }
        }
        $resultToSave = $this->fillNewWithStage($resultData, $helper->getEventId(), $helper->getStageId());
        $resultToSave->upload_type = $helper->getChecker()->preCheckType();

        $resultToSave->result_type = $this->ResultTypes
            ->getCachedWithDefault($helper->getChecker(), $resultData['result_type']['id'] ?? null);

        return $resultToSave;
    }

    public function createSimpleRunnerResult(array $resultData, Runner $runner, UploadHelper $helper): Runner
    {
        $runnerResultToSave = $this->_newResultWithType($resultData, $helper);
        $runnerResultToSave->class_id = $helper->getCurrentClassId();
        return $runner->addRunnerResult($runnerResultToSave);
    }

    public function createRunnerResult(array $resultData, Runner $runner, UploadHelper $helper): Runner
    {
        $helper->getMetrics()->startRunnerResultsTime();
        $runnerResultToSave = $this->_newResultWithType($resultData, $helper);
        $runnerResultToSave->class_id = $helper->getCurrentClassId();

        $runner = $helper->processRunnerResults($runnerResultToSave, $runner);

        $splits = $resultData['splits'] ?? [];
        /** @var RunnerResult $runnerResultToSave */
        $runnerResultToSave = $this->Splits->uploadAllSplits($splits, $runnerResultToSave, $helper);
        if ($runnerResultToSave->hasInvalidFinishTime()) {
            $helper->getMetrics()
                ->setWarning('Runner results has finish_times without time_seconds card:' . $runner->sicard);
        }
        return $runner->addRunnerResult($runnerResultToSave);
    }
}
