<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class OrganizersFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Organizers';
    public const ORGANIZER_1_ID = '1';
    public const ORGANIZER_1_NAME = 'Organizer 1';

    public $records = [
        [
            'id' => OrganizersFixture::ORGANIZER_1_ID,
            'uuid' => null,
            'name' => OrganizersFixture::ORGANIZER_1_NAME,
            'country' => 'Spain',
            'region' => 'Region',
            'created' => $now,
            'modified' => $now,
            'deleted' => null,
        ],
    ];
}
