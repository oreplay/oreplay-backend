<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Model\Entity\ParticipantResultsEntity;
use Results\Model\Entity\Split;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\SplitsTable;

class SplitImporter
{
    private SplitsTable $_splits;
    private ControlsTable $_controls;
    private UploadHelper $_helper;

    public function __construct(SplitsTable $splits, UploadHelper $helper)
    {
        $this->_splits = $splits;
        /** @var ControlsTable $controls */
        $controls = $splits->Controls->getTarget();
        $this->_controls = $controls;
        $this->_helper = $helper;
    }

    public function importInto(
        ParticipantResultsEntity $resultToSave,
        array $splits,
        string $warningMessage = ''
    ): ParticipantResultsEntity {
        $metrics = $this->_helper->getMetrics();
        $metrics->startSplitsTime();
        if ($splits && !$resultToSave->hasSameSplits($splits)) {
            $resultToSave->setHash($splits);
            if (!$this->_helper->getChecker()->isIntermediates()) {
                $this->_splits->deleteAllByRunnerResultId($resultToSave->getId());
            }
            $resultToSave = $this->_addEachSplit($resultToSave, $splits);
        }
        $metrics->endSplitsTime();
        if ($resultToSave->hasInvalidFinishTime()) {
            $metrics->setWarning('Runner results has finish_times without time_seconds' . $warningMessage);
        }
        return $resultToSave;
    }

    private function _addEachSplit(
        ParticipantResultsEntity $resultToSave,
        array $splits
    ): ParticipantResultsEntity {
        $context = $this->_helper->getContext();
        $existingResults = $this->_helper->getExistingResults();
        $metrics = $this->_helper->getMetrics();
        $isIntermediate = $this->_helper->getChecker()->isIntermediates();
        foreach ($splits as $split) {
            if ($this->_isPunchOutOfCourse($split)) {
                continue;
            }
            $split['is_intermediate'] = $isIntermediate;
            $splitToSave = $this->_splits->fillNewWithStage($split, $context->getEventId(), $context->getStageId());
            $splitToSave->class_id = $context->getClassId();
            if ($split['station'] ?? null) {
                $control = $this->_controls->createControlIfNotExists($context, $existingResults, $split);
                $splitToSave->addControl($control);
            }
            $metrics->addOneSplit();
            $resultToSave->addSplit($splitToSave);
        }
        return $resultToSave;
    }

    private function _isPunchOutOfCourse(array $split): bool
    {
        return ($split['status'] ?? '') === Split::STATUS_ADDITIONAL;
    }
}
