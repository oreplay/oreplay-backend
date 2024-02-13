<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class ClubsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Clubs';
    public const CLUB_1 = '83dc3504-9edd-4bbd-80b8-589aa0359c3e';

    public $records = [
        [
            'id' => ClubsFixture::CLUB_1,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'uuid' => null,
            'oe_key' => null,
            'short_name' => 'Club A',
            'long_name' => 'Official Club A from Spain',
            'city' => null,
            'logo' => null,
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ],
    ];
}
