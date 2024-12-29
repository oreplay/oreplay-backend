<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $team_name
 */
class Team extends AppEntity
{
    public const FIRST_TEAM = '8ea9f351-4141-4ff2-891d-9e2a904bc296';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'team_name' => true,
        'bib_number' => true,
    ];

    protected $_virtual = [
        'full_name',
        'overall',
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'team_name',
        'uuid',
        'db_id',
        'iof_id',
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
        'team_results',
        'created',
        'modified',
        'deleted',
    ];

    /**
     * @return TeamResult
     */
    public function _getOverall()
    {
        return $this->team_results[0] ?? null;
    }

    public function _getFullName()
    {
        return $this->team_name;
    }
}
