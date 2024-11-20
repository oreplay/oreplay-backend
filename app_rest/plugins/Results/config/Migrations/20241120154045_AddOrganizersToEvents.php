<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class AddOrganizersToEvents extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('organizer_id', 'string', ['null' => true]);
        $table->update();
    }
}
