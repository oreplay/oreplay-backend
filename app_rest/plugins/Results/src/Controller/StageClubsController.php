<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Lib\ClubMatcher\ClubOrganizerInfo;
use Results\Lib\ClubMatcher\IncludedOrganizerFields;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\OrganizersTable;

class StageClubsController extends ApiController
{
    private ClubsTable $Clubs;
    private ClubOrganizerInfo $organizerInfo;

    public function isPublicController(): bool
    {
        return true;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Clubs = ClubsTable::load();
        $this->organizerInfo = new ClubOrganizerInfo(OrganizersTable::load());
    }

    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $clubs = $this->Clubs->findByStage($eventId, $stageId)->all()->toList();
        $included = IncludedOrganizerFields::fromQueryValue($this->getRequest()->getQuery('include'));
        $this->return = $this->organizerInfo->addTo($clubs, $included);
    }
}
