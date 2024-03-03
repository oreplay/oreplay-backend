<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class Team extends Entity
{
    public const FIRST_TEAM = '8ea9f351-4141-4ff2-891d-9e2a904bc296';

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
        'created',
        'modified',
        'deleted',
    ];
}
