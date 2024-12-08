<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddUploadHash extends AbstractMigration
{
    public function change(): void
    {
        // if no change is done in the whole class, we could totally skip processing
        $table = $this->table('classes');
        $table->addColumn('upload_hash', 'string', [
            'null' => true,
            'after' => 'long_name'
        ]);
        $table->update();

        // if no change is done in the results, we could save time not processing splits
        $table = $this->table('runner_results');
        $table->addColumn('upload_hash', 'string', [
            'null' => true,
            'after' => 'check_time'
        ]);
        $table->update();

        $table = $this->table('runners');
        $table->addColumn('upload_hash', 'string', [
            'null' => true,
            'after' => 'leg_number'
        ]);
        $table->update();
    }
}
