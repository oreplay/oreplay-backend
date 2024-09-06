<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class CoursesFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Courses';
    public const COURSE_1 = '19f0c2f8-bc90-4665-beab-ec218c4f01b2';

    public $records = [
        [
            'id' => CoursesFixture::COURSE_1,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'uuid' => null,
            'oe_key' => null,
            'short_name' => 'ME',
            'long_name' => 'M Elite',
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ],
    ];
}
