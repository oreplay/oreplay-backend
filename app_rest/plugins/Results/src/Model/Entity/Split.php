<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property mixed $reading_time
 * @property mixed $points
 * @property Control $control
 */
class Split extends Entity
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
        'stage_order',
        'sicard',
        'station',
        'reading_milli',
        'runner_result_id',
        'team_result_id',
        'class_id',
        'control_id',
        'id_leg',
        'id_revisit',
        'runner_id',
        'team_id',
        'bib_runner',
        'bib_team',
        'club_id',
        'order_number',
        'battery_perc',
        'battery_time',
        'raw_value',
        'created',
        'modified',
        'deleted',
    ];
}
