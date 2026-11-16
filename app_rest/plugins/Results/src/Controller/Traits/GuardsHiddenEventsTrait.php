<?php

declare(strict_types = 1);

namespace Results\Controller\Traits;

use Results\Model\Table\EventsTable;
use Results\Model\Table\TokensTable;

/**
 * A hidden event is shown to visitors as private, so the public endpoints that serve its data (stages,
 * classes, clubs, results, stats) apply the rule EventsController::getData() applies to the event itself:
 * only its organizers, managers and its desktop client token may read it.
 *
 * For public controllers (isPublicController() true) whose routes carry the {eventID} param.
 */
trait GuardsHiddenEventsTrait
{
    protected function beforeMain($id = null, $secondParam = null)
    {
        $this->_assertEventIsVisibleToRequester((string)$this->request->getParam('eventID'));
        return null;
    }

    private function _assertEventIsVisibleToRequester(string $eventId): void
    {
        $Events = EventsTable::load();
        $event = $Events->find()->select(['id', 'is_hidden'])->where(['id' => $eventId])->first();
        if (!$event || $event->is_hidden !== true) {
            return;
        }
        if (TokensTable::load()->isValidEventToken($eventId, $this->_bearerOfTheRequest())) {
            return;
        }
        $userId = $this->getManualOauth()->verifyAuthorizationAndGetToken()->getUserId();
        $Events->getEventFromUser($eventId, (string)$userId);
    }

    private function _bearerOfTheRequest(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }
}
