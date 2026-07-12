<?php

declare(strict_types = 1);

namespace Rankings\Model\Entity;

use Results\Model\Entity\AppEntity;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string|null $runner_id
 * @property string $stage_order_id
 */
class RankingOrganizer extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'first_name' => true,
        'last_name' => true,
        'runner_id' => true,
    ];

    protected array $_hidden = [
        'deleted',
    ];
}
