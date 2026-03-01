<?php

declare(strict_types = 1);

use Migrations\BaseSeed;
use Results\Model\Entity\ResultType;

class ResultTypesSeed extends BaseSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => ResultType::OVERALL,
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
            [
                'id' => ResultType::PARTIAL_OVERALL,
                'description' => 'Partial Overall',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('result_types');
        $rows = $table->getAdapter()->fetchAll('SELECT count(*) from ' . $table->getName() . ' LIMIT 1');
        $count = $rows[0]['count(*)'] ?? '';
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
