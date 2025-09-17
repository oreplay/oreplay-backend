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
                'description' => 'Classic',
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
                'id' => StageType::SCORE,
                'description' => 'Score',
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
            [
                'id' => StageType::TOTALS,
                'description' => 'Overall',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::ONE_MAN_RELAY,
                'description' => 'One Man Relay',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => StageType::RANKING,
                'description' => 'Ranking',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('stage_types');
        $rows = $table->getAdapter()->fetchAll('SELECT count(*) from ' . $table->getName() . ' LIMIT 1');
        $count = $rows[0][0] ?? '';
        if ($count !== count($data)) {
            foreach ($data as $datum) {
                try {
                    $table->setData([])->insert($datum)->save();
                } catch (\Exception $e) {
                    debug($e->getMessage());
                }
            }
        }
    }
}
