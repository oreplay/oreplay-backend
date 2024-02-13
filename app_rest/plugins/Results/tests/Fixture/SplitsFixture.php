<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;

class SplitsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Splits';

    public const SPLIT_1 = '34ed02e5-da3c-4457-b2cb-4492fea19805';

    public $records = [
        [
            'id' => self::SPLIT_1,
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
    ];
}
