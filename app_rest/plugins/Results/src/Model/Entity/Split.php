<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property mixed $reading_time
 * @property mixed $points
 * @property Control $control
 */
class Split extends AppEntity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'sicard' => true,
        'station' => true,
        'points' => true,
        'reading_time' => true,
        'order_number' => true,
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
        'battery_perc',
        'battery_time',
        'raw_value',
        'created',
        'modified',
        'deleted',
    ];

    public function addControl(Control $control): self
    {
        $this->_fields['control'] = $control;
        return $this;
    }
}
