<?php
declare(strict_types=1);

use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\OauthClientsFixture;
use Migrations\AbstractSeed;

class OauthAccessTokensSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'access_token' => OauthAccessTokensFixture::ACCESS_TOKEN_SELLER,
                'client_id' => OauthClientsFixture::DASHBOARD_CLI,
                'user_id' => '2',
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
