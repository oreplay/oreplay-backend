<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIndicesToUploadLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('upload_logs')
            ->addIndex(['event_id'])
            ->addIndex(['stage_id'])
            ->addIndex(['upload_type'])
            ->addIndex(['created']);
        $table->update();
        $table = $this->table('stage_orders')
            ->addIndex(['created']);
        $table->update();
    }
}
