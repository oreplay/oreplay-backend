<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
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

    public function importInto(Runner $participant, array $resultData): Runner
    {
        $this->_helper->getMetrics()->startRunnerResultsTime();
        $resultToSave = $this->_newResultWithType($resultData);

        $participant = $this->_helper->processRunnerResults($resultToSave, $participant);

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

    private function _newResultWithType(array $resultData): RunnerResult
    {
        $checker = $this->_helper->getChecker();
        $context = $this->_helper->getContext();
        if ($checker->isTotals()) {
            $resultData = $this->_asPartialOverallWhenStage($resultData);
        }
        $resultToSave = $this->_runnerResults
            ->fillNewWithStage($resultData, $context->getEventId(), $context->getStageId());
        $resultToSave->class_id = $context->getClassId();
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
        $this->_helper->getMetrics()->setWarning('Result type STAGE converted to PARTIAL_OVERALL');
        $resultData['result_type'] = ['id' => ResultType::PARTIAL_OVERALL];
        return $resultData;
    }
}
