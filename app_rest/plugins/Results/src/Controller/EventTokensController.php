<?php

declare(strict_types = 1);

namespace Results\Controller;

use Cake\Http\Exception\BadRequestException;
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

    protected function addNew($data)
    {
        $eventId = $this->request->getParam('eventID');
        $userId = $this->OAuthServer->getUserID();
        $this->EventTokens->Events->getEventFromUser($eventId, $userId);
        $expires = $data['expires'] ?? null;
        if (!$expires) {
            throw new BadRequestException('Expires is mandatory');
        }
        $this->return = $this->EventTokens->createTokenForEvent($eventId, $data);
    }
}
