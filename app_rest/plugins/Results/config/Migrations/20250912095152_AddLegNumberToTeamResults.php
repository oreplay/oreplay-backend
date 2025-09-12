<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddLegNumberToTeamResults extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('team_results');
        $table->addColumn('leg_number', 'integer', [
            'default' => 0,
            'null' => false,
            'after' => 'points_bonus',
        ]);
        $table->update();
    }
}
