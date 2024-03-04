<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string $oe_key
 * @property string $short_name
 * @property string $long_name
 */
class Club extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'oe_key',
        'long_name',
        'city',
        'logo',
        'created',
        'modified',
        'deleted',
    ];
}
