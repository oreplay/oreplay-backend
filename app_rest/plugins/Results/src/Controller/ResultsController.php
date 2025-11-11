<?php

declare(strict_types = 1);

namespace Results\Controller;

use RestApi\Lib\RestRenderer;
use RestApi\Model\Table\RestApiTable;
use Results\Lib\Output\DuplicatedRunners;
use Results\Lib\Output\ReadablePointsCsv;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamsTable;

class ResultsController extends ApiController
{
    protected RestApiTable $Teams;
    protected RunnersTable $Runners;

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
        $toRet = $this->_getResults($eventId, $stageId, $filters);
        $this->return = $this->_parseOutput($toRet, $filters);
    }

    protected function _getResults(mixed $eventId, mixed $stageId, array $filters): RestRenderer|array
    {
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
        return $toRet;
    }

    protected function _parseOutput(array $results, array $filters): RestRenderer|array
    {
        $outputType = $filters['output'] ?? null;
        if ($outputType) {
            switch ($outputType) {
                case 'ReadablePointsCsv':
                    $renderer = new ReadablePointsCsv($filters['contrib_text'] ?? null);
                    return $renderer->setResults($results);
                case 'DuplicatedRunners':
                    // TODO if not user with rights we should remove the filter
                    //if (!$this->OAuthServer->isManagerUser()) { // TODO public endpoint dont have OAuthServer
                    //    unset($filters[DuplicatedRunners::PARAM_REMOVE_FROM_RANKING]);
                    //}
                    return DuplicatedRunners::setResults($results, $filters);
            }
        }
        return $results;
    }
}
