<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class ControlTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => 0,
                'description' => 'Normal Control',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 1,
                'description' => 'Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 2,
                'description' => 'Finish',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 3,
                'description' => 'Clear',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => 4,
                'description' => 'Check',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('control_types');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
