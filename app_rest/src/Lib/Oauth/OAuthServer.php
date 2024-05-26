<?php

declare(strict_types = 1);

namespace App\Lib\Oauth;

use App\Controller\ApiController;
use RestApi\Lib\Helpers\OauthBaseServer;
use RestApi\Model\Table\OauthAccessTokensTable;

class OAuthServer extends OauthBaseServer
{
    protected function loadStorage(): OauthAccessTokensTable
    {
        return \App\Model\Table\OauthAccessTokensTable::load();
    }

    protected function silentVerificationPath(): string
    {
        return ApiController::ROUTE_PREFIX . '/me';
    }
}
