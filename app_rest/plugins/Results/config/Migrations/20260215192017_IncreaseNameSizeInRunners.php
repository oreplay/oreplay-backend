<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class IncreaseNameSizeInRunners extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('runners');

        $table->changeColumn('first_name', 'string', [
            'limit' => 100,
            'null' => true,
            'default' => null,
        ])
            ->changeColumn('last_name', 'string', [
                'limit' => 200,
                'null' => true,
                'default' => null,
            ])
            ->update();
    }
}
