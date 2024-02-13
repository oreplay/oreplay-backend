<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;

class ControlsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Controls';

    public const CONTROL_31 = '34ed02e5-da3c-4457-b2cb-4492fea19805';

    public $records = [
        [
            'id' => ControlsFixture::CONTROL_31,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'control_name' => null,
            'station' => 31,
            'coord_system' => null,
            'datum' => null,
            'utm_zone' => null,
            'hemisphere' => null,
            'latitude' => null,
            'longitude' => null,
            'control_type_id' => ControlType::NORMAL,
            'battery_perc' => null,
            'last_reading' => null,
            'created' => '2024-01-02 10:08:11',
            'modified' => '2024-01-02 10:08:11',
            'deleted' => null,
        ],
    ];
}
