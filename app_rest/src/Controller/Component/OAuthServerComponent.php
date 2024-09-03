<?php

declare(strict_types = 1);

namespace App\Controller\Component;

use App\Controller\ApiController;
use App\Lib\Oauth\OAuthServer;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;

class OAuthServerComponent extends Component
{
    /** @var OAuthServer */
    private $server;

    private $_skipAuth = false;

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->server = new OAuthServer($config);
    }

    public function beforeFilter(EventInterface $event)
    {
        /** @var ApiController $controller */
        $controller = $event->getSubject();
        if ($controller->getRequest()->is('options')) {
            return $controller->getResponse();
        }
        $this->server->setupOauth($controller);
        if (!$this->_skipAuth) {
            $this->server->verifyAuthorizationAndGetToken();
            $this->_verifyAdminAction($controller);
            $this->_parseRequestParamIDs($controller);
        }
        if ($controller && $controller->getRequest()->is(['POST', 'PUT', 'PATCH'])) {
            if (!$controller->getRequest()->getData()) {
                throw new BadRequestException('Empty body or invalid Content-Type in HTTP request');
            }
        }
    }

    public function startup(Event $event = null)
    {
        $controller = $event->getSubject();
        if (!$this->_skipAuth) {
            $this->server->authorizeUserData($controller);
        }
    }

    private function _verifyAdminAction(ApiController $controller)
    {
        $path = explode('/', $controller->getRequest()->getPath());
        if ($path[3] === 'admin') {
            if (!$this->server->isAdminUser()) {
                throw new ForbiddenException('Only admins allowed for this action');
            }
        }
    }

    public function isUserAllowed($userID)
    {
        return $this->server->isUserAllowed($userID);
    }

    public function isManagerUser(): bool
    {
        return $this->server->isManagerUser();
    }

    public function getUserGroup()
    {
        return $this->server->getUserGroup();
    }


    public function getUserID()
    {
        return $this->server->getUserID();
    }

    private function _parseRequestParamIDs(Controller $controller)
    {
        $idName = strtolower(substr($controller->getName(), 0, -1)) . 'ID';
        $idValue = $controller->getRequest()->getParam('pass')[0] ?? null;
        if ($idValue !== null) {
            $req = $controller->getRequest();
            $req->withParam($idName, $idValue);
            $controller->setRequest($req);
        } else {
            if (!$controller->getRequest()->is(['POST', 'GET'])) {
                throw new BadRequestException('HTTP method requires ID');
            }
        }
    }
}
