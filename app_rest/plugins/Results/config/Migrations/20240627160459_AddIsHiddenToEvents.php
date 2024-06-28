<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIsHiddenToEvents extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('is_hidden', 'tinyinteger', [
            'after' => 'id',
            'default' => false,
            'limit' => 1,
            'null' => false,
        ]);
        $table->update();
    }
}
