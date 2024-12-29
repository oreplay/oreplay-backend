<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;

class SplitsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Splits';

    public const SPLIT_1 = '34ed02e5-da3c-4457-b2cb-4492fea19805';
    public const SPLIT_2 = 'b8e6927d-a17b-4d24-a10e-bb1f2ed9061c';

    public $records = [
        [
            'id' => self::SPLIT_1,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'stage_order' => null,
            'sicard' => null,
            'station' => 81,
            'reading_time' => '2024-01-02 10:00:10.321',
            'reading_milli' => null,
            'points' => null,
            'runner_result_id' => RunnerResult::FIRST_RES,
            'team_result_id' => null,
            'class_id' => null,
            'control_id' => ControlsFixture::CONTROL_31,
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
            'created' => '2024-01-02 10:00:10',
            'modified' => '2024-01-02 10:00:10',
            'deleted' => null,
        ],
        [
            'id' => self::SPLIT_2,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'stage_order' => null,
            'sicard' => null,
            'station' => 81,
            'reading_time' => '2024-01-03 11:00:20.321',
            'reading_milli' => null,
            'points' => null,
            'runner_result_id' => null,
            'team_result_id' => TeamResultsFixture::TEAM_RESULT_1,
            'class_id' => null,
            'control_id' => ControlsFixture::CONTROL_31,
            'id_leg' => null,
            'id_revisit' => null,
            'runner_id' => null,
            'team_id' => Team::FIRST_TEAM,
            'bib_runner' => null,
            'bib_team' => null,
            'club_id' => null,
            'order_number' => null,
            'battery_perc' => null,
            'battery_time' => null,
            'raw_value' => null,
            'created' => '2024-01-03 10:00:10',
            'modified' => '2024-01-03 10:00:10',
            'deleted' => null,
        ],
    ];
}
