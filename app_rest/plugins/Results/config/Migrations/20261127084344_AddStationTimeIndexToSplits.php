<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddStationTimeIndexToSplits extends BaseMigration
{
    private const NAME = 'idx_splits_stage_station_time';
    private const STAGE = 'splits_stage_id';

    public function up(): void
    {
        $table = $this->table('splits');
        $table->addIndex(['stage_id', 'station', 'reading_time'], ['name' => self::NAME]);
        $table->update();
        if ($table->hasIndexByName(self::STAGE)) {
            $table->removeIndexByName(self::STAGE);
            $table->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('splits');
        $table->addIndex(['stage_id'], ['name' => self::STAGE]);
        $table->update();
        $table->removeIndexByName(self::NAME);
        $table->update();
    }
}
