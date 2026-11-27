<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\FullBaseUrl;
use RestApi\Lib\Helpers\PaginationHelper;
use Results\Lib\ControlPunches;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\TeamsTable;

class ControlPunchesController extends ApiController
{
    private SplitsTable $Splits;

    public function isPublicController(): bool
    {
        return true;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Splits = SplitsTable::load();
    }

    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $station = $this->request->getParam('station');
        $paginator = new PaginationHelper($this->request);
        $paginator->processQueryFilters();

        $query = $this->Splits->findPunchesAtStation($eventId, $stageId, $station);
        $page = $paginator->getReturnArray($query, FullBaseUrl::host());
        $controlPunches = new ControlPunches(RunnersTable::load(), TeamsTable::load(), $eventId, $stageId);
        $page['data'] = $controlPunches->buildRows($page['data']->toArray());

        $this->flatResponse = true;
        $this->return = $page;
    }
}
