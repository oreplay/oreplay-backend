<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Datasource\ResultSetInterface;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;

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

    private function _getExistingDataById(string $id): array
    {
        return $this->_existingData[$id] ?? [];
    }

    public function getExistingDbDataForThisId(string $id, RunnerResult $dataToSave): array
    {
        $toRet = [];
        $resForRunner = $this->_getExistingDataById($id);
        foreach ($resForRunner as $runnerResult) {
            if ($runnerResult->isSameResult($dataToSave)) {
                $toRet[] = $runnerResult;
            }
        }
        return $toRet;
    }
}
