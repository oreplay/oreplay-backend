<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Entity\ClassEntity;

class ResultsByClassController extends ResultsController
{
    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $filters = $this->request->getQueryParams();
        $classes = $this->Runners->Classes->getByStageWithRadios($eventId, $stageId);
        $toRet = [];
        /** @var ClassEntity $class */
        foreach ($classes as $class) {
            $filters['class_id'] = $class->id;
            $toRet[$class->short_name] = $this->_getResults($eventId, $stageId, $filters);
        }
        $this->return = $this->_parseOutput($toRet, $filters['output'] ?? null);
    }
}
