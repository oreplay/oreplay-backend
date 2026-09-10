<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddCountryToEvents extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table->addColumn('country_code', 'string', [
            'after' => 'location',
            'default' => null,
            'limit' => 2,
            'null' => true,
        ]);
        $table->update();
    }
}
