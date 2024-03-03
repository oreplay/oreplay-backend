<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\StageType;

class StageTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => StageType::CLASSIC,
                'description' => 'Foot-O, MTBO, Ski-O',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::MASS_START,
                'description' => 'Mass Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::CHASE_START,
                'description' => 'Chase Start',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::RELAY,
                'description' => 'Relay',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::ROGAINE,
                'description' => 'Rogaine',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::RAID,
                'description' => 'Raid',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::TRAIL,
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
