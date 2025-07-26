<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIsNcAndContributoryToRunnerResults extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table->addColumn('is_nc', 'boolean', [
            'default' => false,
            'null' => false,
            'after' => 'status_code',
        ]);
        $table->addColumn('contributory', 'boolean', [
            'default' => null,
            'null' => true,
            'after' => 'is_nc',
        ]);
        $table->update();
    }
}
