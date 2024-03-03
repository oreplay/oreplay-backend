<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property integer $position
 * @property integer $time_seconds
 * @property Split[] $splits
 * @property string $event_id
 * @property string $stage_id
 * @property string $result_type_id
 * @property FrozenTime $start_time
 */
class RunnerResult extends Entity
{
    public const FIRST_RES = '635af121-db7b-4c5e-82ab-79208e45568f';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'start_time' => true,
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
