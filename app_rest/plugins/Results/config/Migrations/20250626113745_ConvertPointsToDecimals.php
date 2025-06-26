<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class ConvertPointsToDecimals extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table
            ->changeColumn('points_final', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_adjusted', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_penalty', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_bonus', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->update();

        $table = $this->table('team_results');
        $table
            ->changeColumn('points_final', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_adjusted', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_penalty', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->changeColumn('points_bonus', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'default' => 0,
                'null' => true,
            ])
            ->update();
    }
}
