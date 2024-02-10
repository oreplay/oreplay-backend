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

    protected function getMandatoryParams(): array
    {
        return ['eventID', 'stageID'];
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->return = $this->Runners->findRunnersInStage($eventId, $stageId)->all();
    }
}
