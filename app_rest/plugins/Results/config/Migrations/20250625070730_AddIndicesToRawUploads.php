<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIndicesToRawUploads extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('raw_uploads')
            ->addIndex(['event_id'])
            ->addIndex(['stage_id'])
            ->addIndex(['upload_log_id'])
            ->addIndex(['created']);
        $table->update();
    }
}
