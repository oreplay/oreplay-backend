<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Model\Entity\ParticipantResultsEntity;
use Results\Model\Table\SplitsTable;

class SplitsToReplace
{
    private array $_resultIdsByForeignKey = [];

    public function add(ParticipantResultsEntity $result): void
    {
        $this->_resultIdsByForeignKey[$result->getSplitsForeignKey()][$result->getId()] = true;
    }

    public function deleteAndForget(SplitsTable $splits): int
    {
        $deleted = 0;
        foreach ($this->_resultIdsByForeignKey as $foreignKey => $resultIds) {
            $deleted += $splits->deleteAllByResultIds($foreignKey, array_keys($resultIds));
        }
        $this->_resultIdsByForeignKey = [];
        return $deleted;
    }
}
