<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateOauthAccessTokens extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('oauth_access_tokens', ['id' => false, 'primary_key' => 'access_token']);
        $table->addColumn('access_token', 'string', [
            'default' => null,
            'limit' => 40,
            'null' => false,
        ]);
        $table->addColumn('client_id', 'string', [
            'default' => null,
            'limit' => 80,
            'null' => false,
        ]);
        $table->addColumn('user_id', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('expires', 'timestamp', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('scope', 'string', [
            'default' => null,
            'limit' => 2000,
            'null' => true,
        ]);
        if (!$table->exists()) {
            $table->create();
        }
    }
}
