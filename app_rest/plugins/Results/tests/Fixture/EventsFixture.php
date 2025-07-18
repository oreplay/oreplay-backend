<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use DateTime;
use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Organizer;

class EventsFixture extends RestApiFixture
{
    public const string LOAD = 'plugin.Results.Events';

    public const string FIRST_RAID = '1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00';
    public const string EVENT_TODAY = '1b10cfcc-b3f2-40bb-8dbe-8b24c0-today';
    public const string EVENT_TOMORROW_RANKING = '85a7c518-54a8-4180-9708-9dcd4e9906c7';

    public function init(): void
    {
        $this->records = [
            [
                'id' => Event::FIRST_EVENT,
                'description' => 'Test Foot-o',
                'initial_date' => '2024-01-25',
                'final_date' => '2024-01-25',
                'federation_id' => Federation::FEDO,
                'organizer_id' => Organizer::ID,
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
                'organizer_id' => Organizer::ID,
                'created' => '2022-03-07 10:01:00',
                'modified' => '2022-03-07 10:01:00',
                'deleted' => null,
            ],
            [
                'id' => EventsFixture::EVENT_TODAY,
                'description' => 'Today event',
                'initial_date' => (new DateTime('now'))->format('Y-m-d'),
                'final_date' => (new DateTime('now'))->format('Y-m-d'),
                'federation_id' => Federation::IOF,
                'organizer_id' => Organizer::ID,
                'created' => '2022-03-10 10:01:00',
                'modified' => '2022-03-10 10:01:00',
                'deleted' => null,
            ],
            [
                'id' => self::EVENT_TOMORROW_RANKING,
                'description' => 'Tomorrow ranking event',
                'initial_date' => (new DateTime('now'))->modify('+1 day')->format('Y-m-d'),
                'final_date' => (new DateTime('now'))->modify('+1 day')->format('Y-m-d'),
                'federation_id' => Federation::IOF,
                'organizer_id' => Organizer::ID,
                'created' => '2022-03-13 10:01:00',
                'modified' => '2022-03-13 10:01:00',
                'deleted' => null,
            ]
        ];

        parent::init();
    }
}
