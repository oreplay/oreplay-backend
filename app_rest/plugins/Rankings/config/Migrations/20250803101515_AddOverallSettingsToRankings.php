<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddOverallSettingsToRankings extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('rankings');
        $table->addColumn('overall_settings', 'string', [
            'after' => 'status_scores',
            'default' => null,
            'limit' => 500,
            'null' => true,
        ]);
        $table->update();
    }
}
