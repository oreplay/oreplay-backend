<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddExternalResultsUrlToStages extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('stages');
        $table->addColumn('external_results_url', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
            'after' => 'start',
        ]);
        $table->update();
    }
}
