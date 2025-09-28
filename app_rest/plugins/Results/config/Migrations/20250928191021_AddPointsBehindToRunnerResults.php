<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddPointsBehindToRunnerResults extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table->addColumn('points_behind', 'decimal', [
            'precision' => 10,
            'scale' => 4,
            'default' => 0,
            'null' => true,
            'after' => 'points_final',
        ]);
        $table->update();
    }
}
