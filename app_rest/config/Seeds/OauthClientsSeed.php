<?php

declare(strict_types = 1);

use App\Lib\Consts\SeedIds;
use Migrations\BaseSeed;

class OauthClientsSeed extends BaseSeed
{
    public function run(): void
    {
        $data = [
            [
                'client_id' => SeedIds::OAUTH_CLIENT_DASHBOARD_CLI,
                'client_secret' => 'tes7secret_cse446dj',
                'redirect_uri' => '',
                'user_id' => null,
            ]
        ];

        $table = $this->table('oauth_clients');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
