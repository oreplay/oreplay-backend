<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class CreateUploadLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('upload_logs', ['id' => false,
            'collation'=>'utf8mb4_0900_ai_ci', 'primary_key' => ['id']]);

        $table->addColumn('id', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => false,
        ]);
        $table->addColumn('event_id', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => false,
        ]);
        $table->addColumn('stage_id', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => false,
        ]);
        $table->addColumn('upload_type', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => true,
        ]);
        $table->addColumn('state', 'integer', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('upload_status', 'integer', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('info', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('created', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('deleted', 'timestamp', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->create();
    }
}
