<?php

declare(strict_types = 1);

namespace Rankings\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class RankingOrganizersFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Rankings.RankingOrganizers';

    public array $records = [
        [
            'id' => '0198b1f0-1111-7000-8000-000000000001',
            'first_name' => 'Org',
            'last_name' => 'Anizer',
            'runner_id' => null,
            'stage_order_id' => '0198b1f0-2222-7000-8000-000000000002',
            'created' => '2026-07-12 10:00:00',
            'modified' => '2026-07-12 10:00:00',
            'deleted' => null,
        ],
    ];
}
