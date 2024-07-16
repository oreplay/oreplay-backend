<?php

declare(strict_types = 1);

namespace Results\Controller;

use Cake\Http\Exception\BadRequestException;
use Results\Model\Entity\Event;
use Results\Model\Table\TokensTable;

/**
 * @property TokensTable $EventTokens
 */
class EventTokensController extends ApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->EventTokens = TokensTable::load();
    }

    private function _checkIsOwnEvent(): Event
    {
        $eventId = $this->request->getParam('eventID');
        $userId = $this->OAuthServer->getUserID();
        return $this->EventTokens->Events->getEventFromUser($eventId, $userId);
    }

    protected function addNew($data)
    {
        $event = $this->_checkIsOwnEvent();
        $expires = $data['expires'] ?? null;
        if (!$expires) {
            throw new BadRequestException('Expires is mandatory');
        }
        $this->return = $this->EventTokens->createTokenForEvent($event->id, $data);
    }

    protected function getList()
    {
        $event = $this->_checkIsOwnEvent();
        $this->return = $this->EventTokens->findTokensForEvent($event->id)->all();
    }

    protected function delete($id)
    {
        $event = $this->_checkIsOwnEvent();
        $this->EventTokens->expireTokenForEvent($id, $event->id);
        $this->return = false;
    }
}
