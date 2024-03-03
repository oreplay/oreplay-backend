<?php

declare(strict_types = 1);

namespace App\Lib\Oauth;

use App\Lib\Exception\SilentException;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use Cake\Controller\Controller;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\I18n\FrozenTime;
use Cake\Log\LogTrait;
use Cake\ORM\TableRegistry;
use OAuth2\Autoloader;
use OAuth2\Controller\AuthorizeControllerInterface;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server;

class OAuthServer
{
    use LogTrait;

    /** @var Server */
    public $server;
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;

    /** @var OauthAccessTokensTable */
    private $_storage;

    private $_serverConfig = ['enforce_state' => true, 'allow_implicit' => true];

    private $_uid;

    public function __construct(array $config = [])
    {
        $this->_storage = OauthAccessTokensTable::load();

        foreach ($config as $key => $value) {
            $this->{'_' . $key} = $value;
        }
    }

    public function authorizeGroup($controller)
    {
        //if ($controller->groupRestriction) {
        //    $allowedArray = [
        //        GROUP_ADMIN => [GROUP_ADMIN],
        //        GROUP_MODERATOR => [GROUP_ADMIN, GROUP_MODERATOR],
        //        GROUP_SELLER => [GROUP_ADMIN, GROUP_MODERATOR, GROUP_SELLER],
        //    ];
        //    $allowed = $allowedArray[$controller->groupRestriction];
        //    if (!in_array($this->getUserGroup(), $allowed)) {
        //        throw new ForbiddenException('Not allowed for this group');
        //    }
        //}
    }

    public static function parseRequestParamIDs(Controller $controller)
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

    public function authorizeUserData(Controller $controller)
    {
        $userID = $controller->getRequest()->getParam('userID') ?? false;
        if ($userID !== false && !$this->isUserAllowed($userID)) {
            if (method_exists($controller, 'authorizeUserData')) {
                $controller->authorizeUserData();
            } else {
                $extra = $userID . ' -> ' . json_encode($this->_getDependentUserIDs());
                $this->log('ForbiddenException OAuthServer: ' . $extra, 'error');
                throw new ForbiddenException('Resource not allowed with this token');
            }
        }
        $this->_setUserLang();
    }

    public function isUserAllowed($userID): bool
    {
        $uID = $this->getUserID();
        if ($uID == $userID || $this->isManagerUser()) {
            return true;
        }
        return in_array($userID, $this->_getDependentUserIDs());
    }

    private function _getDependentUserIDs(): array
    {
        return $this->_getUserModel()->getDependentUserIDs($this->getUserID()) + [$this->getUserID()];
    }

    public function getUserGroup(): ?int
    {
        if (!$this->getUserID()) {
            throw new InternalErrorException('Empty User ID, used? verifyAuthorization()');
        }
        return $this->_getUserModel()->getUserGroup($this->getUserID());
    }

    public function isManagerUser(): bool
    {
        return false; // in_array($this->getUserGroup(), [GROUP_ADMIN, GROUP_MODERATOR]);
    }

    private function _setUserLang(): void
    {
        //$lang = $this->getUser3LetterLang();
        //LegacyI18n::setLocale($lang);
    }

    private function _getUserModel(): UsersTable
    {
        /** @var UsersTable $table */
        $table = $this->_storage->Users->getTarget();
        return $table;
    }

    public function verifyAuthorization()
    {
        $isAuthorized = $this->server->verifyResourceRequest($this->request, $this->response);
        if (!$isAuthorized) {
            $err = 'Verify authorization error: ' . $this->response->getParameter('error_description');
            $code = $this->response->getStatusCode();
            if (($_SERVER['REQUEST_URI'] ?? '') === '/api/v2/me') {
                throw new SilentException($err, $code);
            } else {
                throw new InternalErrorException($err, $code);
            }
        }
        $token = $this->server->getAccessTokenData($this->request);
        $this->_uid = ($token['user_id'] ?? '') ? $token['user_id'] : $token['client_id'];
        $_SERVER = array_merge(['AUTH_TOKEN_UID' => $this->_uid], $_SERVER);
        return $this->_uid;
    }

    public function setupOauth(Controller $controller)
    {
        Autoloader::register();

        // create array of supported grant types
        $grantTypes = [
            'authorization_code' => new AuthorizationCode($this->_storage),
            'user_credentials' => new UserCredentials($this->_storage),// password
        ];

        unset($_GET['access_token']);
        // add the server to the silex "container" so we can use it
        $this->request = Request::createFromGlobals();

        $authorization = $controller->getRequest()->getEnv('HTTP_AUTHORIZATION');
        if ($authorization) {
            $this->request->headers['AUTHORIZATION'] = explode(',', $authorization)[0];
        }
        $this->response = new Response();
        $allowOrigin = $controller->getResponse()->getHeader('Access-Control-Allow-Origin');
        if (isset($allowOrigin[0])) {
            $this->response->setHttpHeader('Access-Control-Allow-Origin', $allowOrigin[0]);
        }

        // instantiate the oauth server
        $this->server = new Server($this->_storage, $this->_serverConfig, $grantTypes);
        $tokenType = $this->request->headers('X_CT_TOKEN', false);
        if ($tokenType && strtolower($tokenType) == 'jwt') {
            $this->setUseJWT();
        }
    }

    public function setUseJWT($use = true)
    {
        $this->server->setConfig('use_jwt_access_tokens', $use);
    }

    public function getAccessTokenParams($uid, $clientId = null)
    {
        $time = new FrozenTime();
        $authToken = $this->_getAuthorizationTokenWithoutResponse($uid, $clientId);
        $response = $this->_getAccessTokenForDashboard($authToken, $uid, $clientId);
        $response = self::_convert302to200($response);
        $params = $response->getParameters();
        $expiresIn = $this->response->getParameter('expires_in');
        $params['expires_at'] = $time->addSeconds($expiresIn ?? 0);
        return $params;
    }

    private static function _convert302to200(\OAuth2\Response $response)
    {
        if ($response->getStatusCode() === 302) {
            $headers = $response->getHttpHeaders();
            $location = ($headers['Location'] ?? '');
            if (substr($location, 0, 2) == '/#') {
                $params = [];
                parse_str(substr($location, 2), $params);
                return new \OAuth2\Response($params, 200);
            }
        }
        return $response;
    }

    private function _getAuthorizationTokenWithoutResponse($uid, $clientId = null)
    {
        $response = $this->getAuthorizationToken($uid, $clientId);
        if ($response->getStatusCode() != 302 || !$response->getHttpHeader('Location')) {
            throw new BadRequestException(
                ($response->getParameters()['error'] ?? 'Error with access token'),
                $response->getStatusCode()
            );
        }
        parse_str(parse_url($response->getHttpHeader('Location'))['query'], $params);
        return $params['code'];
    }

    private function _getAccessTokenForDashboard($authToken, $uid, $clientId)
    {
        $responseType = AuthorizeControllerInterface::RESPONSE_TYPE_ACCESS_TOKEN;
        $this->request->query['access_token'] = $authToken;
        $this->request->query['user_id'] = $uid;
        return $this->_getAuthorizedAccessToken($clientId, $responseType, true, $uid);
    }

    public function getCurrentAccessToken()
    {
        return $this->server->getAccessTokenData($this->request, $this->response);
    }

    public function getAccessToken(): Response
    {
        $type = AuthorizeControllerInterface::RESPONSE_TYPE_ACCESS_TOKEN;
        $this->_getToken($type);
        $error = $this->response->getParameter('error_description');
        if ($error) {
            $this->response->error_description = $error;
        }
        return $this->response;
    }

    private function _getToken($responseType)
    {
        if (!$this->server) {
            throw new InternalErrorException('Server was not initialized');
        }
        $isAuthorized = $this->server->verifyResourceRequest($this->request, $this->response);

        $aud = 0;
        if ($isAuthorized) {
            $aud = $this->getCurrentAccessToken()['client_id'] ?? '';
        }
        $this->response = $this->_getAuthorizedAccessToken($aud, $responseType, $isAuthorized);
    }

    private function _getAuthorizedAccessToken($clientId, $responseType, $isAuthorized, $uid = null)
    {
        $this->request->query['client_id'] = $clientId;
        $this->request->query['redirect_uri'] = '/';
        $this->request->query['response_type'] = $responseType;
        $this->request->query['state'] = $responseType;
        $this->server->setConfig('use_jwt_access_tokens', false);
        $this->response = $this->server->handleAuthorizeRequest(
            $this->request, $this->response, $isAuthorized, $uid
        );
        return $this->response;
    }

    public function getAuthorizationToken($uid, $clientId)
    {
        $type = AuthorizeControllerInterface::RESPONSE_TYPE_AUTHORIZATION_CODE;
        if (!$this->request) {
            throw new InternalErrorException('setupOauth() is required');
        }
        $this->request->query['client_id'] = $clientId;
        $this->request->query['redirect_uri'] = '/';
        $this->request->query['response_type'] = $type;
        $this->request->query['state'] = $type;
        $this->server->setConfig('use_jwt_access_tokens', false);
        $this->response = $this->server->handleAuthorizeRequest($this->request, $this->response, true, $uid);
        return $this->response;
    }

    public function getUserID()
    {
        return $this->_uid;
    }
}
