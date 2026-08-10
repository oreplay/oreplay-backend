<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use Results\Model\Entity\Control;
use Results\Model\Entity\ParticipantResultsEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;

class ExistingResultsIndex
{
    private StorageHelper $_runnerResults;
    private StorageHelper $_teamResults;
    private array $_controlsByStation = [];
    private array $_controlsToWrite = [];

    public function __construct()
    {
        $this->_runnerResults = new StorageHelper('runner_id');
        $this->_teamResults = new StorageHelper('team_id');
    }

    public function indexRunnerResults(ResultSetInterface $runnerResults): void
    {
        $this->_runnerResults->setExistingData($runnerResults);
    }

    public function indexTeamResults(ResultSetInterface $teamResults): void
    {
        $this->_teamResults->setExistingData($teamResults);
    }

    public function indexControls(ResultSetInterface $controls): void
    {
        $this->_controlsByStation = [];
        $this->_controlsToWrite = [];
        /** @var Control $control */
        foreach ($controls as $control) {
            $this->_controlsByStation[$control->station] = $control;
        }
    }

    public function getExistingControlByStation($stationNumber): ?Control
    {
        return $this->_controlsByStation[$stationNumber] ?? null;
    }

    public function storeControlByStation(Control $control): void
    {
        $this->_controlsByStation[$control->station] = $control;
        $this->_controlsToWrite[$control->station] = true;
    }

    /**
     * Controls are shared by every class of the stage, so each one has to be written at most
     * once per upload. Answers true only for the first split that reaches a control missing
     * from the database; every later split can just point at its id.
     */
    public function takeControlToWrite(Control $control): bool
    {
        if (!isset($this->_controlsToWrite[$control->station])) {
            return false;
        }
        unset($this->_controlsToWrite[$control->station]);
        return true;
    }

    /**
     * @return ParticipantResultsEntity[]
     */
    public function getExistingDbResults(
        Runner|Team $participant,
        RunnerResult|TeamResult $resultToSave
    ): array {
        $storage = $this->_storageFor($resultToSave);
        if (!$storage->isLoaded()) {
            throw new InternalErrorException(
                'Existing results were not indexed, call indexRunnerResults() and indexTeamResults() first'
            );
        }
        return $storage->getExistingDbDataForThisId($participant->id, $resultToSave);
    }

    private function _storageFor(RunnerResult|TeamResult $resultToSave): StorageHelper
    {
        if ($resultToSave instanceof RunnerResult) {
            return $this->_runnerResults;
        }
        return $this->_teamResults;
    }
}
