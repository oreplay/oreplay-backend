<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropDeadSplitsClassIndex extends BaseMigration
{
    private const COLUMNS = ['class_id', 'control_id', 'id_leg', 'id_revisit'];

    // this index existed only to back splits_ibfk_7, dropped in 20260813100000. Lookups by class_id
    // keep an index either way: idx_classes_search_radios and idx_splits_class both lead with it.
    // Matched by columns rather than by name because the name is not the same everywhere: it is
    // class_id where the initial migration created it, and splits_ibfk_7 where MySQL had to create
    // it for the constraint.
    public function up(): void
    {
        $table = $this->table('splits');
        $table->removeIndex(self::COLUMNS);
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('splits');
        $table->addIndex(self::COLUMNS, ['name' => 'class_id']);
        $table->update();
    }
}
