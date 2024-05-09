<?php

declare(strict_types = 1);

namespace App\Lib\Oauth;

use RestApi\Lib\Helpers\OauthBaseServer;
use RestApi\Model\Table\OauthAccessTokensTable;

class OAuthServer extends OauthBaseServer
{
    protected function loadStorage(): OauthAccessTokensTable
    {
        return \App\Model\Table\OauthAccessTokensTable::load();
    }
}
