<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use App\Test\Fixture\UsersFixture;
use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;

class UsersEventsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.UsersEvents';

    public array $records = [
        [
            'user_id' => UsersFixture::USER_ADMIN_ID,
            'event_id' => Event::FIRST_EVENT,
            'is_admin' => false,
            'created' => '2022-03-01 10:01:01',
            'modified' => '2022-03-01 10:01:01',
            'deleted' => null,
        ]
    ];
}
