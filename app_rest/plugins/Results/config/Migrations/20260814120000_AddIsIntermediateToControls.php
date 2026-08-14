<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddIsIntermediateToControls extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('controls');
        $table
            ->addColumn('is_intermediate', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'station',
            ]);
        $table->update();
    }
}
