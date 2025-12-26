<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;

class TeamResultsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.TeamResults';

    public const TEAM_RESULT_1 = '85c52ee5-99f5-4a49-9b5f-4648e48b4861';

    public array $records = [
        [
            'id' => TeamResultsFixture::TEAM_RESULT_1,
            'event_id' => EventsFixture::FIRST_RAID,
            'stage_id' => Stage::FIRST_STAGE,
            'team_id' => Team::FIRST_TEAM,
            'class_id' => null,
            'stage_order' => null,
            'team_uuid' => null,
            'class_uuid' => null,
            'result_type_id' => ResultType::STAGE,
            'check_time' => null,
            'start_time' => '2024-01-03 10:10:00',
            'finish_time' => '2024-01-03 10:15:10',
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
            'created' => '2024-01-03 10:15:05',
            'modified' => '2024-01-03 10:15:05',
            'deleted' => null,
        ],
    ];
}
