<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddOrganizersToEvents extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('organizer_id', 'string', ['null' => true]);
        $table->update();
    }
}
