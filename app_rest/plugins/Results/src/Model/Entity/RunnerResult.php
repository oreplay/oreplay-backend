<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\UploadHelper;

/**
 * @property integer $position
 * @property integer $time_seconds
 * @property integer $status_code
 * @property integer $time_behind
 * @property string $event_id
 * @property string $stage_id
 * @property string $runner_id
 * @property string $result_type_id
 * @property string $upload_hash
 * @property mixed $leg_number
 * @property FrozenTime $start_time
 * @property FrozenTime $finish_time
 * @property ResultType $result_type
 */
class RunnerResult extends AppEntity
{
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
    ];

    public function addSplit(Split $split)
    {
        if (!($this->_fields['splits'] ?? null)) {
            $this->_fields['splits'] = [];
        }
        $this->setDirty('splits');
        $this->_fields['splits'][] = $split;
    }
    /**
     * @return Split[]
     */
    public function getSplits()
    {
        return $this->_fields['splits'];
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

    public function hasSameSplits(array $compareArray): bool
    {
        $uploadHash = UploadHelper::md5Encode($compareArray);
        $existingHash = $this->_fields['upload_hash'] ?? 'hash_run_res_does_not_exist';
        return $existingHash == $uploadHash;
    }

    public function setHash(array $resultData)
    {
        $hash = UploadHelper::md5Encode($resultData);
        //$this->_fields['upload_hash'] = $hash;// this is not properly saving
        $this->setDirty('upload_hash');
    }

    public function setIdToUpdate(string $id): RunnerResult
    {
        $this->id = $id;
        return $this;
    }
}
