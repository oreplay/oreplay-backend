<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropClassesControlsTable extends BaseMigration
{
    // empty in production and in every test, never written by any code path, and superseded by
    // course_controls. down() rebuilds it exactly as 20240125070731_Initial left it, so the drop
    // stays reversible even though the rows are gone for good.
    public function up(): void
    {
        $this->table('classes_controls')->drop()->save();
    }

    public function down(): void
    {
        $table = $this->table('classes_controls', [
            'id' => false,
            'collation' => 'utf8mb4_0900_ai_ci',
            'primary_key' => ['class_id', 'control_id', 'id_leg', 'id_revisit'],
        ]);

        $table
            ->addColumn('class_id', 'string', ['default' => null, 'limit' => 36, 'null' => false])
            ->addColumn('control_id', 'string', ['default' => null, 'limit' => 36, 'null' => false])
            ->addColumn('id_leg', 'integer', ['default' => null, 'null' => false])
            ->addColumn('id_revisit', 'integer', ['default' => null, 'null' => false])
            ->addColumn('event_id', 'string', ['default' => null, 'limit' => 36, 'null' => false])
            ->addColumn('stage_id', 'string', ['default' => null, 'limit' => 36, 'null' => false])
            ->addColumn('description', 'string', ['default' => null, 'limit' => 255, 'null' => true])
            ->addColumn('order_number', 'integer', ['default' => null, 'null' => true])
            ->addColumn('kilometer', 'decimal', [
                'precision' => 6,
                'scale' => 2,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('relative_position', 'integer', ['default' => null, 'null' => true])
            ->addColumn('controls', 'integer', ['default' => null, 'null' => true])
            ->addColumn('created', 'timestamp', ['default' => null, 'limit' => 3, 'null' => true])
            ->addColumn('modified', 'timestamp', ['default' => null, 'limit' => 3, 'null' => true])
            ->addColumn('deleted', 'timestamp', ['default' => null, 'limit' => 3, 'null' => true])
            ->addIndex(['event_id'], ['name' => 'event_id'])
            ->addIndex(['stage_id'], ['name' => 'stage_id'])
            ->addIndex(['control_id'], ['name' => 'control_id'])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stage_id', 'stages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('class_id', 'classes', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('control_id', 'controls', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        $table->create();
    }
}
