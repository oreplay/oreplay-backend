<?php

declare(strict_types = 1);

namespace Results\Model\Traits;

use Cake\Http\Exception\InternalErrorException;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;

trait StoredParticipantTrait
{
    /**
     * @var Runner[]|Team[]
     */
    private array $_storedParticipantsInClass = [];
    private string $_classIdForStoredParticipants = '';

    public function getStoredAllParticipantsInClass(string $eventId, string $stageId, ?string $classId)
    {
        $classIdString = $classId ?: '';
        $this->ifDifferentClassEmptyStoredList($classIdString);
        if (empty($this->_getStoredParticipantsInClass())) {
            $runners = $this->find()
                ->where([
                    'event_id' => $eventId,
                    'stage_id' => $stageId,
                ]);
            if ($classId === null) {
                $runners->where([
                    'class_id is null',
                ]);
            } else {
                $runners->where([
                    'class_id' => $classId,
                ]);
            }
            $this->_classIdForStoredParticipants = $classIdString;
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
     * @return Runner[]|Team[]
     */
    protected function _getStoredParticipantsInClass(): array
    {
        return $this->_storedParticipantsInClass;
    }
}
