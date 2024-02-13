<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class Club extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'uuid',
        'oe_key',
        'long_name',
        'city',
        'logo',
        'created',
        'modified',
        'deleted',
    ];
}
