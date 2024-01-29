<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class StageTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.StageTypes';

    public $records = [
        [
            'id' => 0,
            'description' => 'Foot-O, MTBO, Ski-O',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 1,
            'description' => 'Mass Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 2,
            'description' => 'Chase Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 3,
            'description' => 'Relay',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 4,
            'description' => 'Rogaine',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 5,
            'description' => 'Raid',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 6,
            'description' => 'Trail-O',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
