<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddFieldsToStageOrders extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('stage_orders');
        $table
            ->addColumn('original_event_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'original_stage_id',
            ])
            ->addColumn('is_official', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'stage_order',
            ])
            ->addColumn('computed', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'is_official',
            ])
            ->addColumn('start', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'computed',
            ])
            ->addIndex(['original_event_id']);
        $table->update();
    }
}
