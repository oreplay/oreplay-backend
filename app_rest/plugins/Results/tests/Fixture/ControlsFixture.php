<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class ControlsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Controls';

    public const CONTROL_31 = 'ed98f978-06cf-45ab-9144-26f3ed65a935';
    public const CONTROL_81 = '1676ccff-b6f5-4d8b-b228-ccafc6e9fece';
    public const CONTROL_82 = '216b168d-99a0-415f-a52d-10cd9116d6c7';

    public array $records = [
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
        [
            'id' => ControlsFixture::CONTROL_81,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'control_name' => null,
            'station' => 81,
            'coord_system' => null,
            'datum' => null,
            'utm_zone' => null,
            'hemisphere' => null,
            'latitude' => null,
            'longitude' => null,
            'control_type_id' => ControlType::NORMAL,
            'battery_perc' => null,
            'last_reading' => null,
            'created' => '2025-05-12 10:55:12',
            'modified' => '2025-05-12 10:55:12',
            'deleted' => null,
        ],
        [
            'id' => ControlsFixture::CONTROL_82,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'control_name' => null,
            'station' => 82,
            'coord_system' => null,
            'datum' => null,
            'utm_zone' => null,
            'hemisphere' => null,
            'latitude' => null,
            'longitude' => null,
            'control_type_id' => ControlType::NORMAL,
            'battery_perc' => null,
            'last_reading' => null,
            'created' => '2025-05-12 10:55:12',
            'modified' => '2025-05-12 10:55:12',
            'deleted' => null,
        ],
    ];
}
