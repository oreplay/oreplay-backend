<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class CreateCourseControlsTable extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('course_controls', ['id' => false,
            'collation' => 'utf8mb4_0900_ai_ci', 'primary_key' => ['id']]);

        $table
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
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
            ->addColumn('course_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('order_number', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('station', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('control_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('kilometer', 'decimal', [
                'precision' => 6,
                'scale' => 2,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('deleted', 'timestamp', [
                'default' => null,
                'limit' => 3,
                'null' => true,
            ])
            ->addIndex(['course_id', 'order_number'], [
                'name' => 'uq_course_controls_order',
                'unique' => true,
            ])
            ->addIndex(['course_id', 'order_number', 'deleted'], ['name' => 'idx_course_controls_course'])
            ->addIndex(['stage_id', 'station', 'deleted'], ['name' => 'idx_course_controls_station'])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('stage_id', 'stages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('course_id', 'courses', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('control_id', 'controls', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION']);
        $table->create();
    }
}
