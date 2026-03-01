<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddTimezoneToEvents extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('timezone', 'string', [
            'limit' => 50,
            'null' => true,
            'default' => null,
            'after' => 'final_date'
        ]);
        $table->update();
    }
}
