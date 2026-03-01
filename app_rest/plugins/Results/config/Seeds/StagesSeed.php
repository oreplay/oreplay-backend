<?php

declare(strict_types = 1);

use Migrations\BaseSeed;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;

class StagesSeed extends BaseSeed
{
    protected $seedClasses = [
        StageTypesSeed::class,
    ];

    public function run(): void
    {
        foreach ($this->seedClasses as $seedClass) {
            /** @var BaseSeed $seeder */
            $seeder = new $seedClass;
            $seeder->setAdapter($this->getAdapter());
            $seeder->run();
        }
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => Stage::FIRST_STAGE,
                'event_id' => Event::FIRST_EVENT,
                'description' => 'First stage',
                'base_date' => null,
                'base_time' => null,
                'order_number' => 1,
                'stage_type_id' => StageType::CLASSIC,
                'server_offset' => 0,
                'utc_value' => '',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('stages');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
