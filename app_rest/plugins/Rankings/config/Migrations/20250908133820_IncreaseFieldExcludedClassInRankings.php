<?php

declare(strict_types = 1);

use Migrations\AbstractMigration;

class IncreaseFieldExcludedClassInRankings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rankings')
            ->changeColumn('excluded_class_names', 'string', [
                'length' => 510,
                'default' => null,
                'null' => true,
            ])
            ->update();
    }
}
