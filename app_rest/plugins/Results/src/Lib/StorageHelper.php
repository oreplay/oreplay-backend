<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Datasource\ResultSetInterface;
use Results\Model\Entity\ParticipantResultsEntity;

class StorageHelper
{
    private array $_existingData;
    private string $_foreignKey;

    public function __construct(string $foreignKey)
    {
        $this->_foreignKey = $foreignKey;
    }

    public function setExistingData(ResultSetInterface $existingRunnerResults): void
    {
        $this->_existingData = [];
        foreach ($existingRunnerResults as $runnerResult) {
            $this->_existingData[$runnerResult[$this->_foreignKey]][] = $runnerResult;
        }
    }

    /**
     * @param string $id
     * @return ParticipantResultsEntity[]
     */
    private function _getExistingDataById(string $id): array
    {
        return $this->_existingData[$id] ?? [];
    }

    public function getExistingDbDataForThisId(string $id, $dataToSave): array
    {
        $toRet = [];
        $resForRunner = $this->_getExistingDataById($id);
        foreach ($resForRunner as $participantResult) {
            if ($participantResult->isSameResult($dataToSave)) {
                $toRet[] = $participantResult;
            }
        }
        return $toRet;
    }
}
