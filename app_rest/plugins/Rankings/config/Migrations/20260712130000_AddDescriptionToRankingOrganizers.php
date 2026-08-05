<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddDescriptionToRankingOrganizers extends AbstractMigration
{
    public function change(): void
    {
        $this->table('ranking_organizers')
            ->addColumn('description', 'string', [
                'length' => 255,
                'default' => null,
                'null' => true,
                'after' => 'last_name',
            ])
            ->update();
    }
}
