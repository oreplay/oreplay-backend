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
    private array $_participantsByKey = [];
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
            foreach ($this->_storedParticipantsInClass as $participant) {
                $this->_indexParticipant($participant);
            }
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
            $this->_participantsByKey = [];
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
        $this->_indexParticipant($runnerOrTeam);
    }

    /**
     * Matching used to scan every stored participant of the class for every incoming one, which is
     * quadratic and only shows up when a class is enormous: a relay class of 2 000 took 75 s, of which the
     * scan was 69 %. The index narrows the candidates; the entity's own predicate still decides, so the
     * leg and class rules are unchanged.
     */
    private function _indexParticipant($participant): void
    {
        foreach ($participant->matchingKeys() as $kind => $value) {
            $key = self::_keyOf($value);
            if ($key !== null) {
                $this->_participantsByKey[$kind][$key][] = $participant;
            }
        }
    }

    /**
     * @return Runner[]|Team[]
     */
    protected function _candidatesFor(string $kind, $value): array
    {
        $key = self::_keyOf($value);
        if ($key === null) {
            return [];
        }
        return $this->_participantsByKey[$kind][$key] ?? [];
    }

    /**
     * isSameField() compares with ==, so '07' and '7' are the same bib and have to share a key.
     */
    private static function _keyOf($value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        $asString = (string)$value;
        if ($asString === '') {
            return null;
        }
        return is_numeric($asString) ? 'n:' . (0 + $asString) : 's:' . $asString;
    }

    /**
     * @return Runner[]|Team[]
     */
    protected function _getStoredParticipantsInClass(): array
    {
        return $this->_storedParticipantsInClass;
    }
}
