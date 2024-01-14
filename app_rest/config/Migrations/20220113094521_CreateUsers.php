<?php
declare(strict_types=1);

use App\Model\Table\AppTable;
use Migrations\AbstractMigration;

class CreateUsers extends AbstractMigration
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
        $table = $this->table(AppTable::TABLE_PREFIX . 'users',
            ['collation'=>'utf8mb4_unicode_ci']);
        $table->addColumn('email', 'string', [
            'default' => null,
            'limit' => 160,
            'null' => false,
        ]);
        $table->addColumn('firstname', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);
        $table->addColumn('lastname', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);
        $table->addColumn('password', 'string', [
            'default' => null,
            'limit' => 60,
            'null' => false,
        ]);
        $table->addColumn('group_id', 'integer', [
            'default' => 3,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('deleted', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex(['email'], ['unique' => true]);
        $table->addIndex(['group_id']);
        $table->create();
    }
}
