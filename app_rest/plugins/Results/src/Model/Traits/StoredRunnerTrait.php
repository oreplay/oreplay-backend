<?php

declare(strict_types = 1);

namespace Results\Model\Traits;

use Results\Model\Entity\Runner;

trait StoredRunnerTrait
{
    /**
     * @var Runner[]
     */
    private array $_storedRunnersInClass = [];
    private string $_classIdForStoredRunners = '';

    public function getStoredAllRunnersInClass(string $eventId, string $stageId, string $classId)
    {
        $this->ifDifferentClassEmptyStoredList($classId);
        if (empty($this->_getStoredRunnersInClass())) {
            $runners = $this->find()
                ->where([
                    'event_id' => $eventId,
                    'stage_id' => $stageId,
                    'class_id' => $classId,
                ]);
            $this->_classIdForStoredRunners = $classId;
            $this->_storedRunnersInClass = $runners->all()->toArray();
        }
    }
    public function emptyStoredList()
    {
        $this->ifDifferentClassEmptyStoredList('');
    }
    public function ifDifferentClassEmptyStoredList(string $classId)
    {
        if ($this->_classIdForStoredRunners != $classId) {
            $this->_classIdForStoredRunners = $classId;
            $this->_storedRunnersInClass = [];
        }
    }
    protected function addRunnerInClass(Runner $runner, string $classId)
    {
        $this->ifDifferentClassEmptyStoredList($classId);
        $this->_storedRunnersInClass[] = $runner;
    }

    /**
     * @return Runner[]
     */
    protected function _getStoredRunnersInClass(): array
    {
        return $this->_storedRunnersInClass;
    }
}
