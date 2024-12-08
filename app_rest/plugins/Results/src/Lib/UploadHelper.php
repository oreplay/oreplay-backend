<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\Control;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\StagesTable;

class UploadHelper
{
    private array $_data;
    private string $_eventId;
    private UploadConfigChecker $_checker;
    private array $_existingRunnerResults;
    private array $_existingControls;
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

    public function getStageId(): string
    {
        return $this->_checker->getStageId();
    }

    public static function md5Encode(array $array): string
    {
        return md5(json_encode($array));
    }

    public function validateConfigChecker(): UploadConfigChecker
    {
        $this->_checker = new UploadConfigChecker($this->_data);
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

    public function setExistingData($RunnerResults)
    {
        /** @var RunnerResultsTable $RunnerResults */
        $this->setExistingRunnerResults($RunnerResults->getAllResults($this));
        $this->setExistingControls($RunnerResults->Splits->Controls->getAllControls($this));
    }

    public function setExistingRunnerResults(ResultSetInterface $existingRunnerResults): UploadHelper
    {
        $this->_existingRunnerResults = [];
        /** @var RunnerResult $runnerResult */
        foreach ($existingRunnerResults as $runnerResult) {
            $this->_existingRunnerResults[$runnerResult->runner_id][] = $runnerResult;
        }
        return $this;
    }

    public function setExistingControls(ResultSetInterface $existingRunnerResults): UploadHelper
    {
        $this->_existingControls = [];
        /** @var Control $control */
        foreach ($existingRunnerResults as $control) {
            $this->storeControlByStation($control);
        }
        return $this;
    }

    public function getExistingControlByStation($stationNumber): ?Control
    {
        return $this->_existingControls[$stationNumber] ?? null;
    }

    public function storeControlByStation(Control $control): void
    {
        $stationNumber = $control->station;
        $this->_existingControls[$stationNumber] = $control;
    }

    /**
     * @return RunnerResult[]
     */
    private function _getExistingResultsByRunner(Runner $runner): array
    {
        return $this->_existingRunnerResults[$runner->id] ?? [];
    }

    public function getExistingDbResultsForThisRunner(
        Runner $runner,
        RunnerResult $runnerResultToSave
    ): array {
        $toRet = [];
        $resForRunner = $this->_getExistingResultsByRunner($runner);
        foreach ($resForRunner as $runnerResult) {
            if ($runnerResult->isSameResult($runnerResultToSave)) {
                $toRet[] = $runnerResult;
            }
        }
        return $toRet;
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
