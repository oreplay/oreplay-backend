<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\ResultTypesTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\SplitsTable;

class RunnerResultImporter
{
    private RunnerResultsTable $_runnerResults;
    private ResultTypesTable $_resultTypes;
    private SplitImporter $_splits;
    private UploadHelper $_helper;

    public function __construct(RunnerResultsTable $runnerResults, UploadHelper $helper)
    {
        $this->_runnerResults = $runnerResults;
        /** @var ResultTypesTable $resultTypes */
        $resultTypes = $runnerResults->ResultTypes->getTarget();
        $this->_resultTypes = $resultTypes;
        /** @var SplitsTable $splits */
        $splits = $runnerResults->Splits->getTarget();
        $this->_splits = new SplitImporter($splits, $helper);
        $this->_helper = $helper;
    }

    public function importInto(Runner $participant, array $resultData, ?string $courseId = null): Runner
    {
        [$participant, $resultToSave] = $this->_helper->getMetrics()->measure(
            UploadMetrics::PARTICIPANT_RESULTS,
            fn() => $this->_reconcileWithExistingResults($participant, $resultData, $courseId)
        );

        $splits = $resultData['splits'] ?? [];
        $warningMessage = 'card: ' . $participant->sicard;
        /** @var RunnerResult $resultToSave */
        $resultToSave = $this->_splits->importInto($resultToSave, $splits, $warningMessage);
        return $participant->addRunnerResult($resultToSave);
    }

    public function importSimpleInto(Runner $participant, array $resultData): Runner
    {
        return $participant->addRunnerResult($this->_newResultWithType($resultData));
    }

    private function _reconcileWithExistingResults(
        Runner $participant,
        array $resultData,
        ?string $courseId = null
    ): array {
        $resultToSave = $this->_newResultWithType($resultData, $courseId);
        return [$this->_helper->processRunnerResults($resultToSave, $participant), $resultToSave];
    }

    private function _newResultWithType(array $resultData, ?string $courseId = null): RunnerResult
    {
        $checker = $this->_helper->getChecker();
        $context = $this->_helper->getContext();
        if ($checker->isTotals()) {
            $resultData = $this->_asPartialOverallWhenStage($resultData);
        }
        $resultToSave = $this->_runnerResults
            ->fillNewWithStage($resultData, $context->getEventId(), $context->getStageId());
        // a team member is saved under its team and keeps runners.class_id null, so this is
        // the only place its class is recorded and what getClassesStats() joins on
        $resultToSave->class_id = $context->getClassId();
        $resultToSave->course_id = $courseId ?: ($context->getCourseId() ?: null);
        $resultToSave->upload_type = $checker->preCheckType();
        $resultToSave->result_type = $this->_resultTypes
            ->getCachedWithDefault($checker, $resultData['result_type']['id'] ?? null);
        return $resultToSave;
    }

    private function _asPartialOverallWhenStage(array $resultData): array
    {
        if (($resultData['result_type']['id'] ?? null) !== ResultType::STAGE) {
            return $resultData;
        }
        $this->_helper->getMetrics()->setWarning('Result type STAGE converted to PARTIAL_OVERALL',
            UploadMetrics::CODE_RESULT_TYPE_CONVERTED);
        $resultData['result_type'] = ['id' => ResultType::PARTIAL_OVERALL];
        return $resultData;
    }
}
