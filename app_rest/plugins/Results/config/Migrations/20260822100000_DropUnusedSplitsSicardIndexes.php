<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropUnusedSplitsSicardIndexes extends BaseMigration
{
    private const LECTURA = 'splits_lectura';
    private const LECTURA2 = 'splits_lectura2';
    private const EVENT = 'splits_event_id';

    // Both index splits by the chip that punched, and nothing looks a split up that way: there is no
    // finder and no query filtering splits on sicard, in this application or in the API, which does not
    // even expose the column. They date from the initial schema.
    // Dropped for storage, like idx_splits_class before them: splits carries 6 449 MB of index against
    // 2 037 MB of data in production, so two unused multi-column indexes on the largest table cost disk
    // in every backup and every restore, and a write on every punch.
    // splits_lectura2 cannot simply go: it leads with event_id and is the only index that does, so the
    // splits_ibfk_1 foreign key rests on it and MySQL refuses the drop. It is replaced by an index on
    // event_id alone, which is what the constraint actually needs. stage_id already has its own.
    public function up(): void
    {
        $table = $this->table('splits');
        $table->addIndex(['event_id'], ['name' => self::EVENT]);
        $table->update();
        $table->removeIndexByName(self::LECTURA);
        $table->removeIndexByName(self::LECTURA2);
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('splits');
        $table->addIndex(['sicard', 'station', 'reading_milli'], ['name' => self::LECTURA]);
        $table->addIndex(
            ['event_id', 'stage_id', 'sicard', 'station', 'reading_milli'],
            ['name' => self::LECTURA2]
        );
        $table->update();
        $table->removeIndexByName(self::EVENT);
        $table->update();
    }
}
