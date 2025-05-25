<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateRawUploadsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('raw_uploads', ['id' => false,
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
        $table->addColumn('upload_log_id', 'string', [
            'default' => null,
            'limit' => 36,
            'null' => false,
        ]);

        $table->addColumn('file_data', 'text', [
            'default' => null,
            'limit' => MysqlAdapter::TEXT_LONG, // 16 MB
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
