<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIsNcAndEligibilityToTeams extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('teams');
        $table->addColumn('is_nc', 'boolean', [
            'default' => false,
            'null' => false,
            'after' => 'bib_alt',
        ]);
        $table->addColumn('eligibility', 'string', [
            'after' => 'is_nc',
            'default' => null,
            'limit' => 26,
            'null' => true,
        ]);
        $table->update();
    }
}
