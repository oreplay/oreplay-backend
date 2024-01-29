<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class ResultTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.ResultTypes';

    public $records = [
        [
            'id' => 0,
            'description' => 'Overall',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 1,
            'description' => 'Stage',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 2,
            'description' => 'Trail-O Normal',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 3,
            'description' => 'Trail-O Timed',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => 4,
            'description' => 'Raid Section',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
