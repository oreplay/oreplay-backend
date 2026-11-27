<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class IncreaseNameSizeInTeams extends BaseMigration
{
    public function change(): void
    {
        $this->table('teams')
            ->changeColumn('team_name', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
            ])
            ->update();
    }
}
