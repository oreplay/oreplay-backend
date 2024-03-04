<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;

class RunnerResultsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.RunnerResults';

    public $records = [
        [
            'id' => RunnerResult::FIRST_RES,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'runner_id' => Runner::FIRST_RUNNER,
            'class_id' => null,
            'stage_order' => null,
            'runner_uuid' => null,
            'class_uuid' => null,
            'result_type_id' => ResultType::STAGE,
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
            'created' => '2024-01-02 10:05:05',
            'modified' => '2024-01-02 10:05:05',
            'deleted' => null,
        ],
    ];
}
