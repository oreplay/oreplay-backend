<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use App\Lib\Exception\InvalidPayloadException;
use Results\Lib\Publish\UploadPublisher;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\TeamResultsTable;

/**
 * Runs one upload: every class in it, in order, each committed on its own.
 *
 * v2 only. v1 keeps the copy of this loop it has always had — it is the contract deployed desktop clients
 * hold, so it is left alone. An asynchronous upload would drive this same object.
 */
class UploadProcessor
{
    public function __construct(
        private readonly ClassesTable $classes,
        private readonly UploadPublisher $publisher
    ) {
    }

    public function process(UploadHelper $helper): UploadProgress
    {
        $configChecker = $helper->validateConfigChecker();
        $metrics = $helper->getMetrics();

        $helper->loadExistingResults($this->_runnerResultsTable(), $this->_teamResultsTable());
        if ($configChecker->isStartLists() && $helper->hasAlreadyFinishTimes()) {
            throw new InvalidPayloadException('Cannot add start times when there are already finish times');
        }

        $progress = new UploadProgress();
        $importer = new ClassImporter($this->classes, $helper);

        foreach ($configChecker->getClasses() as $classObj) {
            $class = $this->classes->createIfNotExists($helper->getEventId(), $helper->getStageId(), $classObj);
            $isTakingTooLong = $this->_setIsTakingTooLongWarning($metrics, $progress->classCount());
            if ($this->_needsProcessing($class, $classObj, $helper) && !$isTakingTooLong) {
                $report = $importer->import($classObj, $class);
                $progress->add($report);
                $this->publisher->classImported($helper->getStageId(), $report);
            }
        }

        $this->_markIntermediateStations($helper);

        return $progress;
    }

    private function _markIntermediateStations(UploadHelper $helper): void
    {
        ControlsTable::load()->markIntermediateStations(
            $helper->getStageId(),
            $helper->getIntermediateStations()->toList()
        );
    }

    private function _needsProcessing(ClassEntity $class, array $classObj, UploadHelper $helper): bool
    {
        return $helper->isReprocessingAll() || !$class->isSameUploadHash($classObj);
    }

    private function _setIsTakingTooLongWarning(UploadMetrics $metrics, int $importedSoFar): bool
    {
        $isTakingTooLong = $metrics->isTakingTooLong();
        if ($isTakingTooLong) {
            $msg1 = 'It is taking too long, ';
            $msg2 = $importedSoFar ? 'some data was already processed, but ' : '';
            $msg3 = 'you need to upload again to finish processing';
            $metrics->setWarning($msg1 . $msg2 . $msg3);
        }
        return $isTakingTooLong;
    }

    private function _runnerResultsTable(): RunnerResultsTable
    {
        return $this->classes->Runners->getTarget()->RunnerResults->getTarget();
    }

    private function _teamResultsTable(): TeamResultsTable
    {
        return $this->classes->Teams->getTarget()->TeamResults->getTarget();
    }
}
