<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\TeamRunnerHelper;

/**
 * @property FrozenTime $start_time
 * @property FrozenTime $finish_time
 * @property mixed $position
 * @property integer $status_code
 * @property mixed $is_nc
 * @property mixed $contributory
 * @property mixed $time_seconds
 * @property float|string $points_final
 * @property mixed $stage_order
 * @property ResultType $result_type
 * @property string $upload_hash
 * @property string $result_type_id
 * @property mixed $leg_number
 * @property string $upload_type
 * @property string $note
 */
class TeamResult extends AppEntity implements ParticipantResultsEntity
{
    use ResultTrait;
    use ResultTraitMatcher;

    protected array $_accessible = [
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

    protected array $_virtual = [
    ];

    protected array $_hidden = [
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
        'team_id',
        'team_uuid',
    ];

    public function setStartAndFinish(TeamRunnerHelper $h): void
    {
        if (!$this->start_time) {
            $this->start_time = $h->getFirstStart();
        }
        if (!$this->finish_time) {
            $this->finish_time = $h->getLastFinish();
        }
    }
}
