<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string $oe_key
 * @property string $short_name
 * @property string $long_name
 */
class Club extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
    ];

    protected array $_hidden = [
        'event_id',
        'stage_id',
        'uuid',
        'oe_key',
        'long_name',// maybe display if needed in frontend
        'city',
        'logo',
        'created',
        'modified',
        'deleted',
    ];
}
