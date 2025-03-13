<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddUploadTypeToRunnerResults extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table->addColumn('upload_type', 'string', [
            'length' => 255,
            'null' => true,
            'after' => 'finish_time'
        ]);
        $table->update();
    }
}
