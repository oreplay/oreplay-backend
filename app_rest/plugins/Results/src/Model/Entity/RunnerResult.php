<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\Consts\StatusCode;

/**
 * @property integer $position
 * @property integer $time_seconds
 * @property float|string $points_final
 * @property integer $status_code
 * @property mixed $stage_order
 * @property integer $time_behind
 * @property string $upload_type
 * @property string $event_id
 * @property string $stage_id
 * @property string $runner_id
 * @property string $result_type_id
 * @property string $upload_hash
 * @property mixed $leg_number
 * @property string $class_id
 * @property FrozenTime $start_time
 * @property FrozenTime $finish_time
 * @property ResultType $result_type
 * @property string $note
 */
class RunnerResult extends AppEntity implements ParticipantResultsEntity
{
    use ResultTrait;
    use ResultTraitMatcher;

    public const FIRST_RES = '635af121-db7b-4c5e-82ab-79208e45568f';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'start_time' => true,
        'finish_time' => true,
        'position' => true,
        'status_code' => true,
        'time_seconds' => true,
        'time_behind' => true,
        'time_adjusted' => true,
        'time_penalty' => true,
        'time_bonus' => true,
        'time_neutralization' => true,
        'points_final' => true,
        'points_adjusted' => true,
        'points_penalty' => true,
        'points_bonus' => true,
        'note' => true,
        'stage_order' => true,
        'leg_number' => true,
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
        'upload_hash',
        'modified',
        'deleted',
    ];

    public function hasInvalidFinishTime(): bool
    {
        return !$this->time_seconds && $this->finish_time instanceof FrozenTime;
    }

    public function isDNS(): bool
    {
        return $this->status_code == StatusCode::DNS;
    }

    public function isMP(): bool
    {
        return $this->status_code == StatusCode::MP;
    }

    public function isDNF(): bool
    {
        return $this->status_code == StatusCode::DNF;
    }

    public function isOT(): bool
    {
        return $this->status_code == StatusCode::OT;
    }

    public function isDQF(): bool
    {
        return $this->status_code == StatusCode::DQF;
    }

    public function isNotYetFinished(): bool
    {
        return $this->status_code == StatusCode::OK && !$this->finish_time;
    }

    public function isFinished(): bool
    {
        return $this->status_code == StatusCode::OK && $this->finish_time;
    }

    public function getMatchingClass(): ?ClassEntity
    {
        return $this->_matchingData['Classes'] ?? null;
    }

    public function setSoftDeleted()
    {
        $this->deleted = new FrozenTime();
    }
}
