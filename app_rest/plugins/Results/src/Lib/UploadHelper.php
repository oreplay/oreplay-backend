<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Collection\CollectionInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\InternalErrorException;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\StagesTable;

class UploadHelper
{
    private array $_data;
    private string $_eventId;
    private UploadConfigChecker $_checker;
    private ResultSetInterface $_existingRunnerResults;

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

    public function setExistingRunnerResults(ResultSetInterface $existingRunnerResults)
    {
        $this->_existingRunnerResults = $existingRunnerResults;
        return $this;
    }

    public function getExistingResultsForThisRunner(
        Runner $runner,
        RunnerResult $runnerResultToSave
    ): CollectionInterface {
        return $this->_existingRunnerResults
            ->filter(function ($row) use ($runner, $runnerResultToSave) {
                /** @var RunnerResult $row */
                return $row->isSameResult($runner, $runnerResultToSave);
            });
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
