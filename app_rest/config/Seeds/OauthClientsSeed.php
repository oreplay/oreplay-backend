<?php
declare(strict_types=1);

use App\Test\Fixture\OauthClientsFixture;
use Migrations\AbstractSeed;

class OauthClientsSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'client_id' => OauthClientsFixture::DASHBOARD_CLI,
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
