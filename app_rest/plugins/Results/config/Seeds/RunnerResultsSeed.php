<?php

declare(strict_types = 1);

use Migrations\AbstractSeed;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;

class RunnerResultsSeed extends AbstractSeed
{
    protected $seedClasses = [
        EventsSeed::class,
        StagesSeed::class,
        RunnersSeed::class,
        ResultTypesSeed::class,
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
                'id' => RunnerResult::FIRST_RES,
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'runner_id' => Runner::FIRST_RUNNER,
                'class_id' => null,
                'stage_order' => null,
                'runner_uuid' => null,
                'class_uuid' => null,
                'result_type_id' => ResultType::OVERAL,
                'check_time' => null,
                'start_time' => '2024-01-02 10:00:00.000',
                'finish_time' => '2024-01-02 10:05:10.123',
                'time_seconds' => 310,
                'position' => 1,
                'status_code' => null,
                'time_behind' => 0,
                'time_neutralization' => null,
                'time_adjusted' => null,
                'time_penalty' => null,
                'time_bonus' => null,
                'points_final' => null,
                'points_adjusted' => null,
                'points_penalty' => null,
                'points_bonus' => null,
                'leg_number' => null,
                'created' => $now,
                'modified' => $now,
                'deleted' => null,
            ],
        ];

        $table = $this->table('runner_results');
        if ($table->getAdapter()->fetchAll('SELECT * from ' . $table->getName() . ' LIMIT 1') === []) {
            $table->insert($data)->save();
        }
    }
}
