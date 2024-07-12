<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class CreateOauthAuthorizationCodes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('oauth_authorization_codes', ['id' => false, 'primary_key' => 'authorization_code']);
        $table->addColumn('authorization_code', 'string', [
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
        $table->addColumn('redirect_uri', 'string', [
            'default' => null,
            'limit' => 2000,
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
