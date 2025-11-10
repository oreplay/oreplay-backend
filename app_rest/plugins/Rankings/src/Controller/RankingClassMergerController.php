<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use Cake\Http\Exception\ForbiddenException;
use Rankings\Model\Table\RankingsTable;
use Results\Controller\ApiController;
use Results\Model\Table\RunnersTable;

class RankingClassMergerController extends ApiController
{
    private RankingsTable $Rankings;
    private RunnersTable $Runners;

    public function isPublicController(): bool
    {
        return false;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Rankings = RankingsTable::load();
        $this->Runners = RunnersTable::load();
    }

    protected function getList()
    {
        if (!$this->OAuthServer->isManagerUser()) {
            throw new ForbiddenException('Only manager users can manage rankings.');
        }
        $fromShortName = $this->getRequest()->getQuery('from_class');
        $toShortName = $this->getRequest()->getQuery('to_class');
        $rk = $this->Rankings->getCached($this->request->getParam('rankingID'));

        $eventId = $rk->getEventId();
        $stageId = $rk->getStageId();
        $fromClass = $this->Runners->Classes->getByShortName($eventId, $stageId, $fromShortName);
        $toClass = $this->Runners->Classes->getByShortName($eventId, $stageId, $toShortName);

        $runFrom = $this->Runners->findRunnersInStage($eventId, $stageId, ['class_id' => $fromClass->id])->toArray();
        $runTo = $this->Runners->findRunnersInStage($eventId, $stageId, ['class_id' => $toClass->id])->toArray();
        $matchedRunners = $this->Runners->moveRunnerResultsFromClassTo($runFrom, $runTo);
        $this->Runners->moveRunnersFromClassTo($fromClass->id, $toClass->id);
        $this->Runners->Classes->softDelete($fromClass->id);
        $this->return = [
            'runnersMatched' => $matchedRunners,
            'from' => $fromClass,
            'to' => $toClass,
            'ranking' => $rk,
        ];
    }
}
