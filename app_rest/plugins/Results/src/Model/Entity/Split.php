<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\SplitCompareReason;

/**
 * @property mixed $reading_time
 * @property mixed $points
 * @property Control $control
 * @property mixed $is_intermediate
 * @property mixed $order_number
 * @property mixed $station
 * @property mixed $sicard
 * @property mixed $class_id
 * @property FrozenTime $created
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
        'is_intermediate' => true,
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

    public function isRadio(): bool
    {
        return $this->is_intermediate;
    }

    public function shouldDisplayCurrent(Split $last): SplitCompareReason
    {
        if ($this->order_number != $last->order_number) {
            return new SplitCompareReason(true, '1 foot-o with order numbers');
        }
        if ($this->isRadio()) {
            if (!$this->reading_time) {
                return new SplitCompareReason(false,
                    '10 do not return radios without time, this should never happen');
            }
            if ($last->isRadio()) {
                if ($this->reading_time == $last->reading_time) {
                    return new SplitCompareReason(false,
                        '6 skip current without time if both are radio AND rogaining when any no radio exists');
                } else {
                    return new SplitCompareReason(true,
                        '5 keep current if both radios with different time AND rogaining when different reading_time');
                }
            } else {
                if ($this->reading_time == $last->reading_time) {
                    return new SplitCompareReason(false,
                        '8 skip rogaining when radio with same time');
                } else {
                    return new SplitCompareReason(false,
                        '7 skip rogaining when any no radio exists');
                }
            }
        } else {
            if ($this->reading_time) {
                return new SplitCompareReason(true,
                    '3 keep repeated split as revisited control');
            } else {
                if ($last->reading_time) {
                    return new SplitCompareReason(false,
                        '2 skip without reading time');
                } else {
                    return new SplitCompareReason(true,
                        '4 keep if none has reading time because is DNS or MP');
                }
            }
        }
    }
}
