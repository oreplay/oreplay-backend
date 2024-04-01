<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class ClassesSeed extends AbstractSeed
{
    protected $seedClasses = [
        EventsSeed::class,
        StagesSeed::class,
    ];
    public function run(): void
    {
        foreach ($this->seedClasses as $seedClass) {
            /** @var AbstractSeed $seeder */
            $seeder = new $seedClass;
            $seeder->setAdapter($this->getAdapter());
            $seeder->run();
        }
        $now = date('Y-m-d H:i:00');
        $data = [
            [
                'id' => ClassEntity::ME,
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'course_id' => null,
                'uuid' => null,
                'oe_key' => null,
                'short_name' => 'ME',
                'long_name' => 'M-E long name',
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('classes');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
