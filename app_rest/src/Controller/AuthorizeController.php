<?php

declare(strict_types = 1);

namespace App\Controller;

use RestApi\Lib\Oauth\AuthorizationCodeGrantPkceFlow;

class AuthorizeController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $AuthorizationFlow = new AuthorizationCodeGrantPkceFlow();
        $this->return = [
            'login_challenge' => $AuthorizationFlow->getLoginChallenge($this->getRequest()),
        ];
    }
}
