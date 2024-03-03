<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $short_name
 * @property string $event_id
 * @property string $stage_id
 * @property Runner[] $runners
 */
class ClassEntity extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
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
