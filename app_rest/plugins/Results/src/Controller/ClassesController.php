<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\ClassesTable;

/**
 * @property ClassesTable $Classes
 */
class ClassesController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->return = $this->Classes->findByStage($eventId, $stageId)->all();
    }
}
