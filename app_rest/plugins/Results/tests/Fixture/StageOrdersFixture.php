<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class StageOrdersFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.StageOrders';
    public const STAGE_1 = '83dc3504-9edd-4bbd-80b8-589aa0359c2e';
    public const WITHOUT_ORIGINAL_IDS = 'a1b2c3d4-0000-4000-8000-000000000002';

    public array $records = [
        [
            'id' => StageOrdersFixture::STAGE_1,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'original_stage_id' => Stage::FIRST_STAGE,
            'original_event_id' => Event::FIRST_EVENT,
            'description' => 'Long stage',
            'stage_order' => 1,
            'computed' => '2024-01-02 10:00:05',
            'start' => '2023-11-01 10:01:00',
            'is_official' => false,
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ],
        [
            'id' => StageOrdersFixture::WITHOUT_ORIGINAL_IDS,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => StagesFixture::STAGE_FEDO_2,
            'original_stage_id' => null,
            'original_event_id' => null,
            'description' => 'No links yet',
            'stage_order' => 1,
            'computed' => null,
            'start' => null,
            'is_official' => false,
            'created' => '2024-01-02 10:00:06',
            'modified' => '2024-01-02 10:00:06',
            'deleted' => null,
        ],
    ];
}
