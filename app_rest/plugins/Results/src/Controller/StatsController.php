<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\RunnerResultsTable;

/**
 * @property RunnerResultsTable $RunnerResults
 */
class StatsController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->RunnerResults = RunnerResultsTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');

        $this->return = $this->RunnerResults->getClassesStats($eventId,$stageId);
    }
}
