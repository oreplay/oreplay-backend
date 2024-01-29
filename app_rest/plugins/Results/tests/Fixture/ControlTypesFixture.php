<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class ControlTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.ControlTypes';

    public $records = [
        [
            'id' => 0,
            'description' => 'Normal Control',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 1,
            'description' => 'Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 2,
            'description' => 'Finish',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 3,
            'description' => 'Clear',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 4,
            'description' => 'Check',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
