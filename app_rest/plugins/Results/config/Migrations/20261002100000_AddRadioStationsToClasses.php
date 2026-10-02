<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddRadioStationsToClasses extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('classes');
        $table->addColumn('radio_stations', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
            'after' => 'long_name',
        ]);
        $table->update();
    }
}
