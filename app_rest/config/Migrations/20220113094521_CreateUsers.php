<?php
declare(strict_types=1);

use App\Model\Table\AppTable;
use Migrations\AbstractMigration;

class CreateUsers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users', ['id' => false,
            'collation'=>'utf8mb4_unicode_ci', 'primary_key' => ['id']]);
        $table->addColumn('id', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => false,
        ]);
        $table->addColumn('email', 'string', [
            'default' => null,
            'limit' => 160,
            'null' => false,
        ]);
        $table->addColumn('password', 'string', [
            'default' => null,
            'limit' => 128,
            'null' => true,
        ]);
        $table->addColumn('first_name', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true,
        ]);
        $table->addColumn('last_name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => true,
        ]);
        $table->addColumn('is_admin', 'boolean', [
            'default' => false,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('is_super', 'boolean', [
            'default' => false,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('created', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('deleted', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->create();
    }
}
