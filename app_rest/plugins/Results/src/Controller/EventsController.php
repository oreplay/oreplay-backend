<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\FullBaseUrl;
use App\Lib\Validator\ValidationException;
use Cake\Http\Exception\ForbiddenException;
use RestApi\Lib\Helpers\PaginationHelper;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;
use Results\Model\Table\TokensTable;

/**
 * @property EventsTable $Events
 */
class EventsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $paginator = new PaginationHelper($this->request);
        $filters = $paginator->processQueryFilters();

        $query = $this->Events->findPaginatedEvents($filters);

        $this->flatResponse = true;
        $this->return = $paginator->getReturnArray($query, FullBaseUrl::host());
    }

    protected function getData($id)
    {
        $rootEntity = 'data';
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($id, $token);
        if ($token) {
            if ($isDesktopClientAuthenticated) {
                $rootEntity = 'event';
            } else {
                $this->getLocalOauth()->verifyAuthorizationAndGetToken();
            }
        }
        $this->flatResponse = true;
        /** @var Event $res */
        $res = $this->Events->getEventWithRelations($id);
        if (!$isDesktopClientAuthenticated && $res->is_hidden === true) {
            $this->_getEventFromUser($id);
        }
        if ($isDesktopClientAuthenticated) {
            $res = $res->getVerySimplified();
        }
        $this->return = [
            $rootEntity => $res
        ];
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }

    protected function addNew($data)
    {
        $userId = $this->getLocalOauth()->verifyAuthorizationAndGetToken()->getUserId();
        /** @var Event $event */
        $event = $this->Events->patchFromNewValidatingFederation($data);
        if ($event->getErrors()) {
            throw new ValidationException($event, 400);
        }
        $event->users = [$this->Events->Users->get($userId)];
        $this->return = $this->Events->saveOrFail($event, ['associated' => true]);
    }

    protected function edit($id, $data)
    {
        $event = $this->_getEventFromUser($id);
        $event = $this->Events->patchEntity($event, $data);
        $saved = $this->Events->saveOrFail($event);
        $this->return = $this->Events->get($saved->id);
    }

    protected function delete($id)
    {
        $event = $this->_getEventFromUser($id);
        $this->Events->softDelete($event->id);
        $this->return = false;
    }

    private function _getEventFromUser($id): Event
    {
        $userId = $this->getLocalOauth()->verifyAuthorizationAndGetToken()->getUserId();
        return $this->Events->getEventFromUser($id, $userId);
    }
}
