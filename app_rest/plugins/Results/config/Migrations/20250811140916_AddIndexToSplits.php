<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIndexToSplits extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('classes');
        $table->addIndex(
            ['event_id', 'stage_id'],
            [
                'name' => 'idx_classes_event_stage',
                'unique' => false
            ]
        )->update();
        $table = $this->table('classes');
        $table->addIndex(
            ['deleted'],
            [
                'name' => 'idx_classes_deleted',
                'unique' => false
            ]
        )->update();

        $table = $this->table('splits');
        $table->addIndex(
            ['class_id'],
            [
                'name' => 'idx_splits_class',
                'unique' => false
            ]
        )->update();

        $table = $this->table('splits');
        $table->addIndex(
            ['class_id', 'is_intermediate', 'deleted', 'station'],
            [
                'name' => 'idx_classes_search_radios',
                'unique' => false
            ]
        )->update();
        $table->update();
    }
}
