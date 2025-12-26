<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use RestApi\Lib\Helpers\CookieHelper;

class MeController extends ApiController
{
    private $tokenFromCookie;
    private UsersTable $Users;

    public function initialize(): void
    {
        parent::initialize();
        $this->Users = UsersTable::load();
        $this->tokenFromCookie = $this->_getAccessTokenFromCookie();
        if ($this->tokenFromCookie) {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->tokenFromCookie;
            $this->request = $this->getRequest()->withEnv('HTTP_AUTHORIZATION', $_SERVER['HTTP_AUTHORIZATION']);
        }
    }

    private function _getAccessTokenFromCookie()
    {
        $_cookieHelper = new CookieHelper();
        return $_cookieHelper->readApi2Remember($this->getRequest());
    }

    protected function getList()
    {
        $userID = $this->OAuthServer->getUserID();
        /** @var User $user */
        $user = $this->Users->get($userID);
        $this->return = $user;
        if ($this->tokenFromCookie) {
            $this->return['token'] = [
                'access_token' => $this->tokenFromCookie,
                'token_type' => 'Bearer',
            ];
        }
    }
}
