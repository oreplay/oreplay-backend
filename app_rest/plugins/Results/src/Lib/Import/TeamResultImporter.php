<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;
use Results\Model\Table\ResultTypesTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\TeamResultsTable;

class TeamResultImporter
{
    private TeamResultsTable $_teamResults;
    private ResultTypesTable $_resultTypes;
    private SplitImporter $_splits;
    private UploadHelper $_helper;

    public function __construct(TeamResultsTable $teamResults, UploadHelper $helper)
    {
        $this->_teamResults = $teamResults;
        /** @var ResultTypesTable $resultTypes */
        $resultTypes = $teamResults->ResultTypes->getTarget();
        $this->_resultTypes = $resultTypes;
        /** @var SplitsTable $splits */
        $splits = $teamResults->Splits->getTarget();
        $this->_splits = new SplitImporter($splits, $helper);
        $this->_helper = $helper;
    }

    public function importInto(Team $participant, array $resultData): Team
    {
        $this->_helper->getMetrics()->startRunnerResultsTime();
        $resultToSave = $this->_newResultWithType($resultData);

        $participant = $this->_helper->processRunnerResults($resultToSave, $participant);

        $splits = $resultData['splits'] ?? [];
        /** @var TeamResult $resultToSave */
        $resultToSave = $this->_splits->importInto($resultToSave, $splits);
        return $participant->addTeamResult($resultToSave);
    }

    private function _newResultWithType(array $resultData): TeamResult
    {
        $checker = $this->_helper->getChecker();
        $context = $this->_helper->getContext();
        $resultToSave = $this->_teamResults
            ->fillNewWithStage($resultData, $context->getEventId(), $context->getStageId());
        $resultToSave->upload_type = $checker->preCheckType();
        $resultToSave->result_type = $this->_resultTypes
            ->getCachedWithDefault($checker, $resultData['result_type']['id'] ?? null);
        return $resultToSave;
    }
}
