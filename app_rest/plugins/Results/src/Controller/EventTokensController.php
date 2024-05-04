<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Table\TokensTable;

/**
 * @property TokensTable $EventTokens
 */
class EventTokensController extends ApiController
{
    public function initialize(): void
    {
        $this->EventTokens = TokensTable::load();
        parent::initialize();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        $eventId = $this->request->getParam('eventID');
        // TODO authenticate user
        $this->return = $this->EventTokens->createTokenForEvent($eventId);
    }
}
