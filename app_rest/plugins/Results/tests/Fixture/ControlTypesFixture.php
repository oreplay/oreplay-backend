<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ControlType;

class ControlTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.ControlTypes';

    public $records = [
        [
            'id' => ControlType::NORMAL,
            'description' => 'Normal Control',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ControlType::START,
            'description' => 'Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ControlType::FINISH,
            'description' => 'Finish',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ControlType::CLEAR,
            'description' => 'Clear',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ControlType::CHECK,
            'description' => 'Check',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
