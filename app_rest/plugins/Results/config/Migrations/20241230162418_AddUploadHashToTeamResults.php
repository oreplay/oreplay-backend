<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddUploadHashToTeamResults extends BaseMigration
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
