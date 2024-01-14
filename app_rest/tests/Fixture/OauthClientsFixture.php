<?php

namespace App\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class OauthClientsFixture extends RestApiFixture
{
    const LOAD = 'app.OauthClients';
    const DASHBOARD_CLI = '2658';

    public $table = 'oauth_clients';
    public $records = [];

    public function __construct()
    {
        $this->records[] = [
            'client_id' => self::DASHBOARD_CLI,
            'client_secret' => 'tes7secret_cse446dj',
            'redirect_uri' => '',
            'user_id' => null,
        ];
        parent::__construct();
    }
}
