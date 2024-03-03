<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\StagesTable;

/**
 * @property StagesTable $Stages
 */
class StagesController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $this->return = $this->Stages->find()
            ->where(['event_id' => $eventId])
            ->orderAsc('created')->all();
    }

    protected function getData($id)
    {
        $eventId = $this->request->getParam('eventID');
        $this->return = $this->Stages->find()
            ->where(['id' => $id, 'event_id' => $eventId])
            ->firstOrFail();
    }
}
