<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property mixed $station
 * @property string $control_type_id
 * @property ControlType $control_type
 */
class Control extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'station' => true,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'control_name',
        'coord_system',
        'datum',
        'utm_zone',
        'hemisphere',
        'latitude',
        'longitude',
        'control_type_id',
        'battery_perc',
        'last_reading',
        'created',
        'modified',
        'deleted',
    ];

    public function setTypeNormalIfNotDefined()
    {
        if (!$this->control_type_id) {
            $this->control_type_id = ControlType::NORMAL;
        }
    }
}
