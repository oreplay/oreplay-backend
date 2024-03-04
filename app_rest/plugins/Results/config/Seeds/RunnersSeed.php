<?php

declare(strict_types = 1);

use Cake\Utility\Text;
use Migrations\AbstractSeed;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;

class RunnersSeed extends AbstractSeed
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
                'id' => Runner::FIRST_RUNNER,
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'first_name' => 'First',
                'last_name' => 'Runner',
                'db_id' => null,
                'iof_id' => null,
                'bib_number' => 4444,
                'bib_alt' => null,
                'sicard' => null,
                'sicard_alt' => null,
                'license' => null,
                'national_id' => null,
                'birth_date' => null,
                'sex' => null,
                'telephone1' => null,
                'telephone2' => null,
                'email' => null,
                'user_id' => null,
                'class_id' => null,
                'club_id' => null,
                'team_id' => null,
                'leg_number' => null,
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('runners');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
