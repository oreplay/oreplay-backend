<?php
declare(strict_types=1);

namespace Results\Test\Fixture;

use Results\Model\Entity\StageType;
use RestApi\TestSuite\Fixture\RestApiFixture;

class StageTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.StageTypes';

    public $records = [
        [
            'id' => StageType::CLASSIC,
            'description' => 'Foot-O, MTBO, Ski-O',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::MASS_START,
            'description' => 'Mass Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::CHASE_START,
            'description' => 'Chase Start',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::RELAY,
            'description' => 'Relay',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::ROGAINE,
            'description' => 'Rogaine',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::RAID,
            'description' => 'Raid',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => StageType::TRAIL,
            'description' => 'Trail-O',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
