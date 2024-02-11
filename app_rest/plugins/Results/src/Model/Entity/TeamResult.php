<?php
declare(strict_types=1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class TeamResult extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'team_id',
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
