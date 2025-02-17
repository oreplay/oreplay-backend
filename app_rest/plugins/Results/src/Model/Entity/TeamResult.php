<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;

/**
 * @property FrozenTime $start_time
 * @property FrozenTime $finish_time
 * @property mixed $position
 * @property ResultType $result_type
 * @property string $upload_hash
 * @property string $result_type_id
 * @property mixed $leg_number
 */
class TeamResult extends AppEntity implements ParticipantResultsEntity
{
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
        'created',
        'modified',
        'deleted',
        'team_id',
        'team_uuid',
    ];

    public function setIDsToUpdate(TeamResult $teamResult): TeamResult
    {
        $this->id = $teamResult->id;
        $this->upload_hash = $teamResult->upload_hash;
        $this->setDirty('upload_hash');
        return $this;
    }

    public function isSameResult(RunnerResult $runnerResultToSave): bool
    {
        if ($this->leg_number != $runnerResultToSave->leg_number) {
            return false;
        }
        if ($this->result_type_id != $runnerResultToSave->result_type->id) {
            return false;
        }
        return true;
    }
}
