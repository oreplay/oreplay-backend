<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Component\OAuthServerComponent;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use RestApi\Lib\Helpers\CookieHelper;

/**
 * @property UsersTable $Users
 * @property OAuthServerComponent $OAuthServer
 * @property OauthAccessTokensTable $OauthAccessTokens
 */
class AuthenticationController extends ApiController
{
    public CookieHelper $CookieHelper;

    public function initialize(): void
    {
        parent::initialize();
        $this->Users = UsersTable::load();
        $this->CookieHelper = new CookieHelper();
        $this->OauthAccessTokens = OauthAccessTokensTable::load();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    private function _secsToExpire($data)
    {
        $hasRemember = $data['remember_me'] ?? false;
        $hours = 60 * 60;
        if ($hasRemember) {
            return 48 * $hours + 6;//172806
        } else {
            return 2 * $hours + 6;//7206
        }
    }

    protected function addNew($data)
    {
        switch ($data['grant_type'] ?? null) {
            case 'password':
                $this->return = $this->_loginWithPassword($data);
                break;
            default:
                throw new BadRequestException('Invalid grant_type');
        }
    }

    private function _loginWithPassword($data): array
    {
        $this->_logoutCookie();

        $clientId = $data['client_id'] ?? false;
        if (!$clientId) {
            throw new BadRequestException('Client id is mandatory');
        }
        $usr = $this->Users->checkLogin($data);

        $token = $this->OauthAccessTokens->createBearerToken($usr->id, $clientId, $this->_secsToExpire($data));

        $cookieHelper = new CookieHelper();
        $cookie = $cookieHelper
            ->writeApi2Remember($token['access_token'], $token['expires_in']);
        $this->response = $this->response->withCookie($cookie);
        return $token;
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
        $cookieHelper = new CookieHelper();
        $res = $cookieHelper->popApi2Remember($this->getRequest());
        $this->response = $this->response->withCookie($cookieHelper->cookie);
        return $res;
    }
}
