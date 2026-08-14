<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class DropDeadSplitsForeignKey extends BaseMigration
{
    private const COLUMNS = ['class_id', 'control_id', 'id_leg', 'id_revisit'];

    // splits_ibfk_7 points at classes_controls, which is empty everywhere and superseded by
    // course_controls. It can never fire anyway: MySQL skips a composite foreign key when any of its
    // columns is NULL, and splits.id_leg and splits.id_revisit are NULL in every row.
    // change() is not used because dropping a foreign key is not reversible on its own.
    public function up(): void
    {
        $table = $this->table('splits');
        $table->dropForeignKey(self::COLUMNS);
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('splits');
        // named explicitly, otherwise MySQL renumbers it and the constraint comes back as splits_ibfk_11
        $table->addForeignKey(self::COLUMNS, 'classes_controls', self::COLUMNS, [
            'constraint' => 'splits_ibfk_7',
            'delete' => 'SET_NULL',
        ]);
        $table->update();
    }
}
