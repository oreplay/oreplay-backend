<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropRedundantSplitsClassIndex extends BaseMigration
{
    private const NAME = 'idx_splits_class';

    // redundant with idx_classes_search_radios (class_id, is_intermediate, deleted, station), which
    // leads with the same column, so both WHERE class_id = ? and the splits_ibfk_5 foreign key keep
    // an index once this is gone.
    // Dropped for storage, not for speed: the write-time payoff was measured at ~206 ms on a
    // 16 738-split upload, below the noise of that benchmark, and this was reverted once on those
    // grounds. What justifies it now is that splits carries 6 449 MB of index against 2 037 MB of
    // data in production, so a redundant index costs disk in every backup and every restore.
    public function up(): void
    {
        $table = $this->table('splits');
        $table->removeIndexByName(self::NAME);
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('splits');
        $table->addIndex(['class_id'], ['name' => self::NAME, 'unique' => false]);
        $table->update();
    }
}
