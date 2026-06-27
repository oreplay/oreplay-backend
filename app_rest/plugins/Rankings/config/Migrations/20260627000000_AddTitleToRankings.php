<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddTitleToRankings extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('rankings');
        $table->addColumn('title', 'string', [
            'after' => 'id',
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->update();
    }
}
