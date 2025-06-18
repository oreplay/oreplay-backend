<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class CreateRanking extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('rankings', ['id' => false, 'collation' => 'utf8mb4_general_ci']);
        $table
            ->addColumn('id', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('scoring_algorithm', 'string', [
                'length' => 150,
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
            ->addColumn('max_points', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'null' => false,
            ])
            ->addColumn('round_precision', 'integer', [
                'null' => false,
            ])
            ->addColumn('nc_true', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'null' => true,
            ])
            ->addColumn('nc_false', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'null' => true,
            ])
            ->addColumn('status_scores', 'string', [
                'length' => 255,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('excluded_class_names', 'string', [
                'length' => 255,
                'default' => null,
                'null' => true,
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
            ->addIndex(['stage_id']);
        $table->create();
    }
}
