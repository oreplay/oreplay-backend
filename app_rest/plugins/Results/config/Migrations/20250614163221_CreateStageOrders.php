<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class CreateStageOrders extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('stage_orders', ['id' => false, 'collation' => 'utf8mb4_general_ci']);
        $table
            ->addColumn('id', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('event_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('stage_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('original_stage_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('stage_order', 'integer', [
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'length' => 255,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'length' => 3,
                'null' => false,
            ])
            ->addColumn('modified', 'timestamp', [
                'length' => 3,
                'null' => false,
            ])
            ->addColumn('deleted', 'timestamp', [
                'length' => 3,
                'null' => true,
            ])
            ->addIndex(['event_id'])
            ->addIndex(['stage_id'])
            ->addIndex(['original_stage_id'])
            ->addIndex(['stage_order']);
        $table->create();
    }
}
