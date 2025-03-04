<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\ClubsTable;

/**
 * @property ClubsTable $Clubs
 */
class StageClubsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Clubs = ClubsTable::load();
    }

    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->return = $this->Clubs->findByStage($eventId, $stageId)->all();
    }
}
