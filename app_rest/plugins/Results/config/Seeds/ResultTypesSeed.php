<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\ResultType;

class ResultTypesSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => ResultType::OVERAL,
                'description' => 'Overall',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ResultType::STAGE,
                'description' => 'Stage',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ResultType::TRAIL_NORMAL,
                'description' => 'Trail-O Normal',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ResultType::TRAIL_TIMED,
                'description' => 'Trail-O Timed',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
            [
                'id' => ResultType::RAID_SECTION,
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
