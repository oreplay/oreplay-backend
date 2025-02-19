<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class AddIsIntermediateToSplits extends AbstractMigration
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
