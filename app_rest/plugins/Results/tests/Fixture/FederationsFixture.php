<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Federation;

class FederationsFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.Federations';

    public array $records = [
        [
            'id' => Federation::FEDO,
            'description' => 'FEDO SICO',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => Federation::IOF,
            'description' => 'IOF OEVENTOR',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
