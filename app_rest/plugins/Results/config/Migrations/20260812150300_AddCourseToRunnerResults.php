<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddCourseToRunnerResults extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('runner_results');
        $table
            ->addColumn('course_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
                'after' => 'class_id',
            ])
            ->addIndex(['course_id'], ['name' => 'idx_runner_results_course'])
            ->addForeignKey('course_id', 'courses', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION']);
        $table->update();
    }
}
