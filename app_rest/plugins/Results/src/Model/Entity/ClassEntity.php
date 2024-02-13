<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property mixed $short_name
 */
class ClassEntity extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'course_id',
        'uuid',
        'oe_key',
        'long_name',
        'created',
        'modified',
        'deleted',
    ];
}
