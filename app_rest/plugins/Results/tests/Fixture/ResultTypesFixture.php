<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\ResultType;

class ResultTypesFixture extends RestApiFixture
{
    const LOAD = 'plugin.Results.ResultTypes';

    public array $records = [
        [
            'id' => ResultType::OVERALL,
            'description' => 'Overall',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ResultType::STAGE,
            'description' => 'Stage',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ResultType::TRAIL_NORMAL,
            'description' => 'Trail-O Normal',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ResultType::TRAIL_TIMED,
            'description' => 'Trail-O Timed',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ResultType::RAID_SECTION,
            'description' => 'Raid Section',
            'created' => '2023-01-01 10:01:00',
            'modified' => '2023-01-01 10:01:00',
            'deleted' => null,
        ],
        [
            'id' => ResultType::PARTIAL_OVERALL,
            'description' => 'Partial Overall',
            'created' => '2025-06-01 10:01:00',
            'modified' => '2025-06-01 10:01:00',
            'deleted' => null,
        ],
    ];
}
