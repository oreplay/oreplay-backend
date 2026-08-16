<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\Control;
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
        $resultToSave = $metrics->measure(
            UploadMetrics::SPLITS,
            fn() => $this->_replaceSplitsWhenChanged($resultToSave, $splits)
        );
        if ($resultToSave->hasInvalidFinishTime()) {
            $metrics->setWarning('Runner results has finish_times without time_seconds' . $warningMessage);
        }
        return $resultToSave;
    }

    private function _replaceSplitsWhenChanged(
        ParticipantResultsEntity $resultToSave,
        array $splits
    ): ParticipantResultsEntity {
        if (!$splits || $this->_isAlreadyStored($resultToSave, $splits)) {
            return $resultToSave;
        }
        $resultToSave->setHash($splits);
        if ($this->_replacesEveryStoredSplit()) {
            $this->_helper->getSplitsToReplace()->add($resultToSave);
        } else {
            $this->_helper->getSplitsToReplace()->addIntermediatesOnly($resultToSave);
        }
        return $this->_addEachSplit($resultToSave, $splits);
    }

    private function _isAlreadyStored(ParticipantResultsEntity $resultToSave, array $splits): bool
    {
        if ($this->_helper->isReprocessingAll() && $this->_replacesEveryStoredSplit()) {
            return false;
        }
        return $resultToSave->hasSameSplits($splits);
    }

    private function _replacesEveryStoredSplit(): bool
    {
        return !$this->_helper->getChecker()->isIntermediates();
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
                $this->_linkControl($splitToSave, $control);
                if ($isIntermediate) {
                    $this->_helper->getIntermediateStations()->add((string)$split['station']);
                }
            }
            $metrics->addOneSplit();
            $splitToSave->linkToResult($resultToSave);
            $this->_helper->getRowsToInsert()->addSplit($splitToSave);
            $resultToSave->addSplit($splitToSave);
        }
        return $resultToSave;
    }

    private function _linkControl(Split $splitToSave, Control $control): void
    {
        if ($this->_helper->getExistingResults()->takeControlToWrite($control)) {
            $this->_helper->getRowsToInsert()->addControl($control);
            $splitToSave->addControl($control);
        }
        $splitToSave->control_id = $control->id;
    }

    private function _isPunchOutOfCourse(array $split): bool
    {
        return ($split['status'] ?? '') === Split::STATUS_ADDITIONAL;
    }
}
