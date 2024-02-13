<?php
declare(strict_types=1);

namespace Results\Controller;

use Results\Model\Table\RunnersTable;

/**
 * @property RunnersTable $Runners
 */
class RunnersController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $filters = $this->request->getQueryParams();
        $this->return = $this->Runners->findRunnersInStage($eventId, $stageId, $filters)->all();
    }
}
