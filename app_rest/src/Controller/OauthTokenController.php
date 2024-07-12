<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Component\OAuthServerComponent;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventManagerInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use RestApi\Lib\Helpers\CookieHelper;
use RestApi\Lib\Oauth\AuthorizationCodeGrantPkceFlow;

/**
 * @property UsersTable $Users
 * @property OAuthServerComponent $OAuthServer
 * @property OauthAccessTokensTable $OauthAccessTokens
 */
class OauthTokenController extends ApiController
{
    public CookieHelper $CookieHelper;

    public function __construct(
        $cookieHelper,
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?string $name = null,
        ?EventManagerInterface $eventManager = null,
        ?ComponentRegistry $components = null
    ) {
        $this->CookieHelper = $cookieHelper;
        parent::__construct($request, $response, $name, $eventManager, $components);
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Users = UsersTable::load();
        $this->OauthAccessTokens = OauthAccessTokensTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        $AuthorizationFlow = new AuthorizationCodeGrantPkceFlow();
        switch ($data['grant_type'] ?? null) {
            case 'password':
                $this->_logoutCookie();
                list($this->response, $this->return) = $AuthorizationFlow->loginWithPassword(
                    $data, $this->CookieHelper, $this->response, $this->OauthAccessTokens);
                break;
            case 'authorization_code':
                $this->return = $AuthorizationFlow->authorizationCodePkceFlow(
                    $data, $this->CookieHelper, $this->getRequest(), $this->OauthAccessTokens);
                break;
            default:
                throw new BadRequestException('Invalid grant_type');
        }
    }

    protected function delete($id)
    {
        $accessToken = $this->_logoutCookie();
        if ($id !== 'current') {
            $accessToken = $id;
        }
        if ($accessToken) {
            $this->OauthAccessTokens->expireAccessToken($accessToken);
        }
        $this->return = false;
    }

    private function _logoutCookie()
    {
        $res = $this->CookieHelper->popApi2Remember($this->getRequest());
        $this->response = $this->response->withCookie($this->CookieHelper->cookie);
        return $res;
    }
}
