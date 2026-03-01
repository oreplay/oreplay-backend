<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddStartToStages extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('stages');
        $table->addColumn('start', 'datetime', [
            'null' => true,
            'default' => null,
            'after' => 'stage_type_id'
        ]);
        $table->update();
    }
}
