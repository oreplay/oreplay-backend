<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class CreateRankingOrganizers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('ranking_organizers', ['id' => false, 'collation' => 'utf8mb4_general_ci']);
        $table
            ->addColumn('id', 'string', [
                'length' => 36,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('first_name', 'string', [
                'length' => 255,
                'null' => false,
            ])
            ->addColumn('last_name', 'string', [
                'length' => 255,
                'null' => false,
            ])
            ->addColumn('runner_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('stage_order_id', 'string', [
                'default' => null,
                'limit' => 36,
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
            ->addIndex(['runner_id'])
            ->addIndex(['stage_order_id']);
        $table->create();
    }
}
