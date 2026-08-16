<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Model\Entity\ParticipantResultsEntity;
use Results\Model\Table\SplitsTable;

class SplitsToReplace
{
    private array $_resultIdsByForeignKey = [];
    private array $_intermediateResultIdsByForeignKey = [];

    public function add(ParticipantResultsEntity $result): void
    {
        $this->_resultIdsByForeignKey[$result->getSplitsForeignKey()][$result->getId()] = true;
    }

    /**
     * A radio upload resends every punch the runner has made so far, so its stored punches have to
     * go or they are written twice. It carries none of the downloaded chip readings, though, so
     * those must survive — which is why the whole result cannot be replaced.
     */
    public function addIntermediatesOnly(ParticipantResultsEntity $result): void
    {
        $this->_intermediateResultIdsByForeignKey[$result->getSplitsForeignKey()][$result->getId()] = true;
    }

    public function deleteAndForget(SplitsTable $splits): int
    {
        $deleted = 0;
        foreach ($this->_resultIdsByForeignKey as $foreignKey => $resultIds) {
            $deleted += $splits->deleteAllByResultIds($foreignKey, array_keys($resultIds));
        }
        foreach ($this->_intermediateResultIdsByForeignKey as $foreignKey => $resultIds) {
            $deleted += $splits->deleteAllIntermediateByResultIds($foreignKey, array_keys($resultIds));
        }
        $this->_resultIdsByForeignKey = [];
        $this->_intermediateResultIdsByForeignKey = [];
        return $deleted;
    }
}
