<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;

class StagesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.Stages';
    const STAGE_FEDO_2 = '8f45d409-72bc-4cdc-96e9-0a2c4504d964';
    const STAGE_RAID = '91c54cd6-98de-441c-a71c-cda466c1abc3';

    public $records = [
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
            'created' => '2023-11-01 10:01:00',
            'modified' => '2023-11-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => self::STAGE_FEDO_2,
            'event_id' => Event::FIRST_EVENT,
            'description' => 'Second stage',
            'base_date' => null,
            'base_time' => null,
            'order_number' => 1,
            'stage_type_id' => StageType::CLASSIC,
            'server_offset' => 0,
            'utc_value' => '',
            'created' => '2023-11-01 10:02:00',
            'modified' => '2023-11-01 10:02:00',
            'deleted' => null,
        ],
        [
            'id' => self::STAGE_RAID,
            'event_id' => EventsFixture::FIRST_RAID,
            'description' => 'Stage raid',
            'base_date' => null,
            'base_time' => null,
            'order_number' => 1,
            'stage_type_id' => StageType::RAID,
            'server_offset' => 0,
            'utc_value' => '',
            'created' => '2023-11-01 10:02:00',
            'modified' => '2023-11-01 10:02:00',
            'deleted' => null,
        ],
    ];
}
