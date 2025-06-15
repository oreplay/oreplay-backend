<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Http\Exception\InternalErrorException;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\TeamResultsTable;

class UploadHelper implements UploadInterface
{
    use UploadControlsTrait;

    private array $_data;
    private string $_eventId;
    private string $_currentClassId;
    private UploadConfigChecker $_checker;
    private StorageHelper $_existingRunnerResults;
    private StorageHelper $_existingTeamResults;
    private UploadMetrics $_metrics;

    public function __construct(array $data, string $eventID)
    {
        if (!$data) {
            throw new InternalErrorException('Payload $data is mandatory');
        }
        if (!$eventID) {
            throw new InternalErrorException('$eventID is mandatory');
        }
        $this->_data = $data;
        $this->_eventId = $eventID;
        $this->_metrics = new UploadMetrics();
    }

    public function getMetrics(): UploadMetrics
    {
        return $this->_metrics;
    }

    public function getData(): array
    {
        return $this->_data;
    }

    public function getEventId(): string
    {
        return $this->_eventId;
    }

    public function setCurrentClassId(string $id): void
    {
        $this->_currentClassId = $id;
    }

    public function getCurrentClassId(): string
    {
        return $this->_currentClassId;
    }

    public function getStageId(): string
    {
        return $this->_checker->getStageId();
    }

    public static function md5Encode(array $array): string
    {
        return md5(json_encode($array)); // NOSONAR
    }

    public function setConfigChecker(UploadConfigChecker $checker): void
    {
        $this->_checker = $checker;
    }

    public function validateConfigChecker(): UploadConfigChecker
    {
        $this->setConfigChecker(new UploadConfigChecker($this->_data));
        if ($this->_checker->isTotals()) {
            $Stages = StagesTable::load();
            if (!$this->_checker->isStageTotals($Stages)) {
                $stage = $Stages->getOrCreateTotalsInEvent($this->getEventId());
                $this->_checker->overwriteStageId($stage->id);
            }
        }

        $checker = $this->_checker->validateStructure($this->getEventId());

        $this->_validateStageInEvent($this->getEventId(), $this->getStageId());

        return $checker;
    }

    private function _validateStageInEvent($eventId, string $stageId): void
    {
        $stage = StagesTable::load()->findByEvent($stageId, $eventId)->first();
        if (!$stage) {
            throw new DetailedException("The stage $stageId is not from the event $eventId");
        }
    }

    public function setExistingData($RunnerResults, $TeamResults)
    {
        /** @var RunnerResultsTable $RunnerResults */
        $this->_existingRunnerResults = new StorageHelper('runner_id');
        $this->_existingRunnerResults->setExistingData($RunnerResults->getAllResults($this));
        /** @var TeamResultsTable $TeamResults */
        $this->_existingTeamResults = new StorageHelper('team_id');
        $this->_existingTeamResults->setExistingData($TeamResults->getAllResults($this));
        $this->setExistingControls($RunnerResults->Splits->Controls->getAllControls($this));
    }

    public function getExistingDbResultsForThisRunner(
        Runner $runner,
        RunnerResult $runnerResultToSave
    ): array {
        return $this->_existingRunnerResults->getExistingDbDataForThisId($runner->id, $runnerResultToSave);
    }

    public function getExistingDbResultsForThisTeam(
        Team $team,
        TeamResult $teamResultToSave
    ): array {
        return $this->_existingTeamResults->getExistingDbDataForThisId($team->id, $teamResultToSave);
    }

    public function getChecker(): UploadConfigChecker
    {
        return $this->_checker;
    }

    public function hasAlreadyFinishTimes()
    {
        return RunnerResultsTable::load()
            ->hasFinishTimes($this->getEventId(), $this->getStageId());
    }
}
