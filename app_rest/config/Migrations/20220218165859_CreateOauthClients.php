<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateOauthClients extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('oauth_clients', ['id' => false, 'primary_key' => 'client_id']);
        $table->addColumn('client_id', 'string', [
            'default' => null,
            'limit' => 80,
            'null' => false,
        ]);
        $table->addColumn('client_secret', 'string', [
            'default' => null,
            'limit' => 80,
            'null' => false,
        ]);
        $table->addColumn('redirect_uri', 'string', [
            'default' => null,
            'limit' => 2000,
            'null' => false,
        ]);
        $table->addColumn('grant_types', 'string', [
            'default' => null,
            'limit' => 80,
            'null' => true,
        ]);
        $table->addColumn('scope', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
        ]);
        $table->addColumn('user_id', 'string', [
            'default' => null,
            'limit' => 80,
            'null' => true,
        ]);
        if (!$table->exists()) {
            $table->create();
        }
    }
}
