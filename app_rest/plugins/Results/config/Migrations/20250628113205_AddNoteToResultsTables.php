<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddNoteToResultsTables extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table->addColumn('note', 'string', [
            'after' => 'points_bonus',
            'default' => null,
            'limit' => 150,
            'null' => true,
        ]);
        $table->update();

        $table = $this->table('team_results');
        $table->addColumn('note', 'string', [
            'after' => 'points_bonus',
            'default' => null,
            'limit' => 150,
            'null' => true,
        ]);
        $table->update();
    }
}
