<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamsTable;

/**
 * @property RunnersTable $Runners
 * @property TeamsTable $Teams
 */
class ResultsController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Teams = TeamsTable::load();
        $this->Runners = RunnersTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $filters = $this->request->getQueryParams();
        $teams = $this->Teams->findTeamsInStage($eventId, $stageId, $filters)->toArray();
        $runners = $this->Runners->findRunnersInStage($eventId, $stageId, $filters)->toArray();
        $isSameDay = (bool)($filters['forceSameDay'] ?? false);
        $toRet = [];
        /** @var Team $team */
        foreach ($teams as $team) {
            $toRet[] = $team;
            $team->_getOverall()->setCompareWithoutDay($isSameDay);
        }
        /** @var Runner $team */
        foreach ($runners as $runner) {
            $toRet[] = $runner;
            $runner->_getOverall()->setCompareWithoutDay($isSameDay);
        }
        $this->return = $toRet;
    }
}
