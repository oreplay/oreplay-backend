<?php

declare(strict_types = 1);

namespace App\Test\Fixture;

use App\Lib\Consts\SeedIds;
use RestApi\TestSuite\Fixture\RestApiFixture;

class OauthClientsFixture extends RestApiFixture
{
    const LOAD = 'app.OauthClients';
    const DASHBOARD_CLI = SeedIds::OAUTH_CLIENT_DASHBOARD_CLI;

    public string $table = 'oauth_clients';
    public array $records = [];

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
