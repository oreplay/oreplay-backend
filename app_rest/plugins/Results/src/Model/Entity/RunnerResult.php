<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class RunnerResult extends Entity
{
    public const FIRST_RES = '635af121-db7b-4c5e-82ab-79208e45568f';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'runner_id',
        'class_id',
        'stage_order',
        'runner_uuid',
        'class_uuid',
        'check_time',
        'time_neutralization',
        'time_adjusted',
        'time_penalty',
        'time_bonus',
        'points_adjusted',
        'points_penalty',
        'points_bonus',
        'leg_number',
        'created',
        'modified',
        'deleted',
    ];
}
