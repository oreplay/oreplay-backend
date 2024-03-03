<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\ControlType;

class ControlTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => ControlType::NORMAL,
                'description' => 'Normal Control',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ControlType::START,
                'description' => 'Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ControlType::FINISH,
                'description' => 'Finish',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ControlType::CLEAR,
                'description' => 'Clear',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ControlType::CHECK,
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
