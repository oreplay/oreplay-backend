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

    public array $records = [
        [
            'id' => StageOrdersFixture::STAGE_1,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'original_stage_id' => Stage::FIRST_STAGE,
            'description' => 'Long stage',
            'stage_order' => 1,
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ],
    ];
}
