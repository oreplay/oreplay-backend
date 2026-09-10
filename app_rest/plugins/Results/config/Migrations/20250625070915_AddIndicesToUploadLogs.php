<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddIndicesToUploadLogs extends BaseMigration
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
