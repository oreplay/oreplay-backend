<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddIsHiddenToEvents extends BaseMigration
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
