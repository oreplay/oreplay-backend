<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Controller\ApiController;
use App\Lib\Exception\DetailedException;
use App\Lib\Oauth\OAuthServer;
use App\Model\Entity\Trainer;
use App\Model\Table\ServicesTable;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
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
            $this->server->verifyAuthorization();
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

    public function isSellerWithAllAccess()
    {
        $allowAccessAllBuyers = Configure::read('Platform.allowAccessAllBuyers');
        $isSeller = $this->isSellerUser();
        return $isSeller && $allowAccessAllBuyers;
    }

    public function isUserAllowed($userID)
    {
        return $this->server->isUserAllowed($userID);
    }

    public function isTrainerUser(): bool
    {
        return $this->server->isTrainerUser();
    }

    public function isSellerUser(): bool
    {
        return $this->server->isSellerUser();
    }

    public function isManagerUser(): bool
    {
        return $this->server->isManagerUser();
    }

    public function getUserGroup()
    {
        return $this->server->getUserGroup();
    }

    public function applyTrainerFiltersToBookings($query, $sellerId)
    {
        $servicesId = $this->checkAccessServiceByTrainer($sellerId);
        if ($servicesId) {
            $query = $query->where(['Bookings.target_event_id IN' => $servicesId]);
        }
        return $query;
    }

    public function applyTrainerFiltersToServices($query, $sellerId)
    {
        $servicesId = $this->checkAccessServiceByTrainer($sellerId);
        if ($servicesId) {
            $query = $query->where(['Services.id IN' => $servicesId]);
        }
        return $query;
    }

    public function checkAccessServiceByTrainer($sellerId): ?array
    {
        if ($this->isTrainerUser()) {
            $isSameParent = $this->getTrainerParent() == $sellerId;
            if (!$isSameParent || $this->isTrainerWithGrantLevel(Trainer::ACCESS_NONE)) {
                throw new ForbiddenException('Resource not allowed with this token');
            } else if ($this->isTrainerWithGrantLevel(Trainer::ACCESS_RESTRICTED)) {
                $Services = ServicesTable::load();
                $serviceIDs = $Services->getServicesIdByTrainer($sellerId, $this->getUserID());
                if (count($serviceIDs) < 1) {
                    throw new DetailedException('No data for this trainer');
                }
                return $serviceIDs;
            }
        }
        return null;
    }

    public function getTrainerParent()
    {
        return $this->server->getTrainerParent();
    }

    public function checkTrainerPermissionsService($serviceId, $sellerId): bool
    {
        return $this->server->checkTrainerPermissionsService($serviceId, $sellerId);
    }

    public function getUserID()
    {
        return $this->server->getUserID();
    }

    public function getUser3LetterLang()
    {
        return $this->server->getUser3LetterLang();
    }

    private function _parseRequestParamIDs(Controller $controller)
    {
        $idName = strtolower(substr($controller->getName(), 4, -1)) . 'ID';
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
