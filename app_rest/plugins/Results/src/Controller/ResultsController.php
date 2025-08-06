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
        // uncomment next line after upgrade rest api plugin 0.7.12
        //$filters = \RestApi\Lib\Helpers\PaginatorHelper::processQueryFiltersStatic($filters);
        $teams = $this->Teams->findTeamsInStage($eventId, $stageId, $filters)->toArray();
        $runners = $this->Runners->findRunnersInStage($eventId, $stageId, $filters)->toArray();
        $isSameDay = (bool)($filters['forceSameDay'] ?? false);
        $toRet = array_merge($teams, $runners);

        $splitsToRemove = [];
        $isAllTotals = true;
        /** @var Team|Runner $res */
        foreach ($toRet as $res) {
            $stage = $res->_getStage();
            if ($stage) {
                $stage->setCompareWithoutDay($isSameDay);
                $splitsToRemove = array_merge($splitsToRemove, $stage->getSplitsToRemove());
            }
            $isAllTotals = $isAllTotals && $res->isTotals();
        }
        $this->Runners->RunnerResults->Splits->softDeleteMany($splitsToRemove);
        if ($isAllTotals) {
            $toRet = RunnersTable::sortTotals($toRet);
        }
        $this->return = $toRet;
    }
}
