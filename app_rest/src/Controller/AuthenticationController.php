<?php

namespace App\Controller;

use App\Controller\Component\OAuthServerComponent;
use App\Lib\Oauth\OAuthServer;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use RestApi\Lib\Helpers\CookieHelper;

/**
 * @property UsersTable $Users
 * @property OAuthServerComponent $OAuthServer
 */
class AuthenticationController extends ApiController
{
    /** @var CookieHelper */
    public $CookieHelper;

    public function initialize(): void
    {
        parent::initialize();
        $this->Users = UsersTable::load();
        $this->CookieHelper = new CookieHelper();
    }

    public function isPublicController(): bool
    {
        return true;
    }

    protected function getMandatoryParams(): array
    {
        return [];
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

    private function _get($uid, array $data): array
    {
        $grantType = $data['grant_type'] ?? '';
        if ($grantType !== 'password') {
            throw new BadRequestException('grant_type should be password');
        }
        $config = [
            'skipAuth' => true,
            'serverConfig' => [
                'enforce_state' => true,
                'allow_implicit' => true,
                'access_lifetime' => $this->_secsToExpire($data)
            ],
        ];
        $server = new OAuthServer($config);
        $server->setupOauth($this);
        return $server->getAccessTokenParams($uid, $data['client_id'] ?? null);
    }

    protected function addNew($data)
    {
        $res = $this->Users->checkLogin($data);

        $token = $this->_get($res->id, $data);

        $this->CookieHelper->writeApi2Remember($token['access_token'], $token['expires_in']);
        unset($token['expires_at']);
        unset($token['state']);

        $this->return = $token;
        $this->return['user'] = $res;
    }

    protected function delete($id)
    {
        $accessToken = $this->_getAccessToken($this->CookieHelper->popApi2Remember($this->request));
        if ($accessToken) {
            OauthAccessTokensTable::load()->expireAccessToken($accessToken);
        }
        $this->return = false;
    }

    private function _getAccessToken($accessToken): ?string
    {
        if ($accessToken) {
            return $accessToken;
        } else {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $exploded = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
                $accessToken = ($exploded[1] ?? null);
            }
            if ($accessToken) {
                return $accessToken;
            } else {
                return null;
            }
        }
    }
}
