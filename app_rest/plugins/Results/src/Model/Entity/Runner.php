<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;
use Model\Entity\Club;

/**
 * @property RunnerResult[] $runner_results
 * @property TeamResult[] $team_results
 * @property string $first_name
 * @property string $last_name
 * @property Club $club
 */
class Runner extends Entity
{
    public const FIRST_RUNNER = 'd08fa43b-ddf8-47f6-9a59-2f1828881765';

    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'uuid',
        'db_id',
        'iof_id',
        'bib_number',
        'bib_alt',
        'sicard',
        'sicard_alt',
        'license',
        'national_id',
        'birth_date',
        'sex',
        'telephone1',
        'telephone2',
        'email',
        'user_id',
        'class_id',
        'class_uuid',
        'club_id',
        'team_id',
        'leg_number',
        'created',
        'modified',
        'deleted',
    ];
}
