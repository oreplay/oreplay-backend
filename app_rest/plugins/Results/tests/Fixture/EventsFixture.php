<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use DateTime;
use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;

class EventsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Events';

    public const FIRST_RAID = '1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00';

    public function init (): void
    {
        $this->records = [
            [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test Foot-o',
                'initial_date' => '2024-01-25',
                'final_date' => '2024-01-25',
                'federation_id' => Federation::FEDO,
                'created' => '2022-03-01 10:01:00',
                'modified' => '2022-03-01 10:01:00',
                'deleted' => null,
            ],
            [
                'id' => EventsFixture::FIRST_RAID,
                'description' => 'Test Adventure Race',
                'initial_date' => '2024-01-26',
                'final_date' => '2024-01-26',
                'federation_id' => Federation::IOF,
                'created' => '2022-03-07 10:01:00',
                'modified' => '2022-03-07 10:01:00',
                'deleted' => null,
            ],
            [
                'id' => '1b10cfcc-b3f2-40bb-8dbe-8b24c0-today',
                'description' => 'Today event',
                'initial_date' => (new DateTime('now'))->format('Y-m-d'),
                'final_date' => (new DateTime('now'))->format('Y-m-d'),
                'federation_id' => Federation::IOF,
                'created' => '2022-03-10 10:01:00',
                'modified' => '2022-03-10 10:01:00',
                'deleted' => null,
            ],
            [
            'id' => '1b10cfcc-b3f2-40bb-8dbe-8b2-tomorrow',
            'description' => 'Tomorrow event',
            'initial_date' => (new DateTime('now'))->modify("+1 day")->format('Y-m-d'),
            'final_date' => (new DateTime('now'))->modify("+1 day")->format('Y-m-d'),
            'federation_id' => Federation::IOF,
            'created' => '2022-03-13 10:01:00',
            'modified' => '2022-03-13 10:01:00',
            'deleted' => null,
        ]
        ];

        parent::init();
    }


}
