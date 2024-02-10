<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;

class EventsFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.Events';

    public $records = [
        [
            'id' => Event::FIRST_EVENT,
            'description' => 'Test event',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => Federation::FEDO,
            'created' => '2022-03-01 10:01:00',
            'modified' => '2022-03-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
