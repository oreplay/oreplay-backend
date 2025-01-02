<?php

declare(strict_types = 1);

namespace Results\Model\Traits;

use Cake\Http\Exception\InternalErrorException;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;

trait StoredParticipantTrait
{
    /**
     * @var Runner[]
     */
    private array $_storedParticipantsInClass = [];
    private string $_classIdForStoredParticipants = '';

    public function getStoredAllParticipantsInClass(string $eventId, string $stageId, string $classId)
    {
        $this->ifDifferentClassEmptyStoredList($classId);
        if (empty($this->_getStoredParticipantsInClass())) {
            $runners = $this->find()
                ->where([
                    'event_id' => $eventId,
                    'stage_id' => $stageId,
                    'class_id' => $classId,
                ]);
            $this->_classIdForStoredParticipants = $classId;
            $this->_storedParticipantsInClass = $runners->all()->toArray();
        }
    }
    public function emptyStoredList()
    {
        $this->ifDifferentClassEmptyStoredList('');
    }
    public function ifDifferentClassEmptyStoredList(string $classId)
    {
        if ($this->_classIdForStoredParticipants != $classId) {
            $this->_classIdForStoredParticipants = $classId;
            $this->_storedParticipantsInClass = [];
        }
    }

    /**
     * @param Runner|Team $runnerOrTeam
     * @param string $classId
     * @return void
     */
    protected function addParticipantInClass($runnerOrTeam, string $classId)
    {
        if (!$runnerOrTeam instanceof Runner && !$runnerOrTeam instanceof Team) {
            throw new InternalErrorException('$runnerOrTeam needs to be instance of Team or Runner');
        }
        $this->ifDifferentClassEmptyStoredList($classId);
        $this->_storedParticipantsInClass[] = $runnerOrTeam;
    }

    /**
     * @return Runner[]
     */
    protected function _getStoredParticipantsInClass(): array
    {
        return $this->_storedParticipantsInClass;
    }
}
