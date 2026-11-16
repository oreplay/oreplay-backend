<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\ClassesTable;
use Results\Controller\Traits\GuardsHiddenEventsTrait;

class StageClassesController extends ApiController
{
    use GuardsHiddenEventsTrait;

    private ClassesTable $Classes;

    public function isPublicController(): bool
    {
        return true;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Classes = ClassesTable::load();
    }

    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->return = $this->Classes->getByStageWithRadios($eventId, $stageId);
    }
}
