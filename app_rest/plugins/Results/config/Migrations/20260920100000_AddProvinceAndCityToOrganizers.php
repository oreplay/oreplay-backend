<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddProvinceAndCityToOrganizers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('organizers');
        $table
            ->addColumn('province', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'after' => 'region',
            ])
            ->addColumn('city', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'after' => 'province',
            ]);
        $table->update();
    }
}
