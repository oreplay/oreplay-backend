<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class ClassesFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Classes';

    public $records = [
        [
            'id' => ClassEntity::ME,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'course_id' => null,
            'uuid' => null,
            'oe_key' => null,
            'short_name' => 'ME',
            'long_name' => 'M Elite',
            'created' => '2024-01-02 10:00:18',
            'modified' => '2024-01-02 10:00:18',
            'deleted' => null,
        ],
        [
            'id' => ClassEntity::FE,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'course_id' => null,
            'uuid' => null,
            'oe_key' => null,
            'short_name' => 'FE',
            'long_name' => 'F Elite',
            'created' => '2024-01-02 10:00:18',
            'modified' => '2024-01-02 10:00:18',
            'deleted' => null,
        ],
    ];
}
