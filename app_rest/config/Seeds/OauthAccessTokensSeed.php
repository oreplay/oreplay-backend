<?php

declare(strict_types = 1);

use App\Lib\Consts\SeedIds;
use Migrations\BaseSeed;

class OauthAccessTokensSeed extends BaseSeed
{
    public function run(): void
    {
        $data = [
            [
                'access_token' => SeedIds::OAUTH_ACCESS_ADMIN_PROVIDER,
                'client_id' => SeedIds::OAUTH_CLIENT_DASHBOARD_CLI,
                'user_id' => SeedIds::USER_ADMIN_ID,
                'expires' => (date('Y') + 1) . '-05-20 17:20:05',
                'scope' => null,
            ]
        ];

        $table = $this->table('oauth_access_tokens');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
