<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;

class ResultTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => 0,
                'description' => 'Overall',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 1,
                'description' => 'Stage',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 2,
                'description' => 'Trail-O Normal',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 3,
                'description' => 'Trail-O Timed',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 4,
                'description' => 'Raid Section',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('result_types');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
