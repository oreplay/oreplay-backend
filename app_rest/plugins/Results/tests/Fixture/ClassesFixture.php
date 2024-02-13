<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class ClassesFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Classes';
    public const ME = '5fd0e06d-eb65-478a-878e-b1b7919ee327';

    public $records = [
        [
            'id' => ClassesFixture::ME,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'course_id' => null,
            'uuid' => null,
            'oe_key' => null,
            'short_name' => 'ME',
            'long_name' => 'Male Elite',
            'created' => '2024-01-02 10:00:18',
            'modified' => '2024-01-02 10:00:18',
            'deleted' => null,
        ],
    ];
}
