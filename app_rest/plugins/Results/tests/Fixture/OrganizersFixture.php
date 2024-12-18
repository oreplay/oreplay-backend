<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Organizer;

class OrganizersFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.Organizers';

    public $records = [
        [
            'id' => Organizer::ID,
            'external_id' => null,
            'name' => Organizer::NAME,
            'country' => 'ES',
            'region' => 'ES-VC',
            'created' => '2022-03-01 10:01:00',
            'modified' => '2022-03-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
