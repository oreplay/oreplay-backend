<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddFieldsToCourses extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('courses');
        $table
            ->addColumn('upload_hash', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'after' => 'long_name',
            ])
            ->addColumn('is_ordered', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'controls',
            ])
            // short_name leads so this index cannot become the only support for the stage_id
            // foreign key, which would make it undroppable and this migration irreversible
            ->addIndex(['short_name', 'stage_id', 'deleted'], ['name' => 'idx_courses_stage_name']);
        $table->update();
    }
}
