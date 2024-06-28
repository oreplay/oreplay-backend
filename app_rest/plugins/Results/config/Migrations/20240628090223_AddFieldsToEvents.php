<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddFieldsToEvents extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('location', 'string', [
            'after' => 'description',
            'default' => null,
            'limit' => 120,
            'null' => true,
        ]);
        $table->addColumn('scope', 'string', [
            'after' => 'description',
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('website', 'string', [
            'after' => 'description',
            'default' => null,
            'limit' => 120,
            'null' => true,
        ]);
        $table->addColumn('picture', 'string', [
            'after' => 'description',
            'default' => null,
            'limit' => 120,
            'null' => true,
        ]);
        $table->update();
    }
}
