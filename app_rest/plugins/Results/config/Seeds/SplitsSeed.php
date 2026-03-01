<?php

declare(strict_types = 1);

use Cake\Utility\Text;
use Migrations\BaseSeed;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;

class SplitsSeed extends BaseSeed
{
    protected $seedClasses = [
        RunnerResultsSeed::class,
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
                'id' => Text::uuid(),
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'stage_order' => null,
                'sicard' => null,
                'station' => null,
                'reading_time' => '2024-01-02 10:00:10',
                'reading_milli' => null,
                'points' => null,
                'runner_result_id' => RunnerResult::FIRST_RES,
                'team_result_id' => null,
                'class_id' => null,
                'control_id' => null,
                'id_leg' => null,
                'id_revisit' => null,
                'runner_id' => Runner::FIRST_RUNNER,
                'team_id' => null,
                'bib_runner' => null,
                'bib_team' => null,
                'club_id' => null,
                'order_number' => null,
                'battery_perc' => null,
                'battery_time' => null,
                'raw_value' => null,
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('splits');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
