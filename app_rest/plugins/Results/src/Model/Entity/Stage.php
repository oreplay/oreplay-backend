<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class Stage extends Entity
{
    public const FIRST_STAGE = '51d63e99-5d7c-4382-a541-8567015d8eed';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected $_hidden = [
        'event_id',
        'base_date',
        'base_time',
        'order_number',
        'stage_type_id',
        'server_offset',
        'utc_value',
        'created',
        'modified',
        'deleted',
        'deleted',
    ];
}
