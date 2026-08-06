<?php

declare(strict_types = 1);

use Migrations\BaseMigration;

class AddIncludedClassNamesToRankings extends BaseMigration
{
    public function change(): void
    {
        $this->table('rankings')
            ->addColumn('included_class_names', 'string', [
                'length' => 510,
                'default' => null,
                'null' => true,
                'after' => 'excluded_class_names',
            ])
            ->update();
    }
}
