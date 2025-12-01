<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

use Cake\Http\Exception\InternalErrorException;
use Results\Model\Entity\Runner;

class ClubList
{
    private array $_clubs = [];

    public function addRunner(Runner $runner, AgeGroupDefinitions $ageGroupDefinitions): void
    {
        $club = $runner->_getClub();
        if (!$club) {
            throw new InternalErrorException('Runner without club: ' . $runner->_getFullName());
        }
        $clubId = $club->id;
        if (!isset($this->_clubs[$clubId])) {
            $this->_clubs[$clubId] = new ClubWithRunners($ageGroupDefinitions, $club);
        }
        $this->_clubs[$clubId]->addRunnerToClub($runner);
    }

    /**
     * @return array<ClubWithRunners>
     */
    public function getOrderedByPointsPerClub(): array
    {
        $runnersList = array_values($this->_clubs);
        usort($runnersList, function (ClubWithRunners $a, ClubWithRunners $b) {
            return $b->getPointsPerClub() <=> $a->getPointsPerClub();
        });
        return $runnersList;
    }
}
