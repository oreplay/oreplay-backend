<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddIsIntermediateToSplits extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('splits');
        $table->addColumn('is_intermediate', 'boolean', [
            'default' => false,
            'limit' => null,
            'null' => false,
            'after' => 'sicard'
        ]);
        $table->update();
    }
}
