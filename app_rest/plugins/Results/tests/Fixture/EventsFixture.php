<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class EventsFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.Events';
    const EVENT_ID = 1;

    public $records = [
        [
            'id' => self::EVENT_ID,
            'description' => 'Test event',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => null,
            'created' => '2022-03-01 10:01:00',
            'modified' => '2022-03-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
