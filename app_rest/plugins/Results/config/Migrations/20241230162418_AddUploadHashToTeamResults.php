<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddUploadHashToTeamResults extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('team_results');
        $table->addColumn('upload_hash', 'string', [
            'null' => true,
            'after' => 'check_time'
        ]);
        $table->update();
    }
}
