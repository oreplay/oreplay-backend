<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class StageTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => 0,
                'description' => 'Foot-O, MTBO, Ski-O',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 1,
                'description' => 'Mass Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 2,
                'description' => 'Chase Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 3,
                'description' => 'Relay',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 4,
                'description' => 'Rogaine',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 5,
                'description' => 'Raid',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 6,
                'description' => 'Trail-O',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('stage_types');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
