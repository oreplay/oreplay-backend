<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\FullBaseUrl;
use Cake\Http\Exception\ForbiddenException;
use RestApi\Lib\Helpers\PaginationHelper;
use Results\Model\Entity\Event;
use Results\Model\Table\EventsTable;

/**
 * @property EventsTable $Events
 */
class EventsController extends ApiController
{
    public const FAKE_TOKEN = 'fake_token';
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
        $isDesktopClientAuthenticated = $token === EventsController::FAKE_TOKEN;
        if ($token) {
            if ($isDesktopClientAuthenticated) {
                $rootEntity = 'event';
            } else {
                throw new ForbiddenException('Invalid Bearer token');
            }
        }
        $this->flatResponse = true;
        $res = $this->Events->getEventWithRelations($id);
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
        $event = $this->Events->patchFromNewWithUuid($data);
        $event->users = [$this->Events->Users->get($userId)];
        $this->return = $this->Events->saveOrFail($event, ['associated' => true]);
    }
}
