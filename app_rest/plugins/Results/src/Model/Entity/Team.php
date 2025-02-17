<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use RestApi\Lib\Exception\DetailedException;

/**
 * @property string $team_name
 * @property mixed $bib_number
 * @property string $class_id
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

    public function addTeamResult(TeamResult $teamResult): Team
    {
        if (!isset($this->team_results)) {
            $this->team_results = [];
        }
        $this->team_results[] = $teamResult;
        return $this;
    }

    public function addRunner(Runner $runner): Team
    {
        if (!isset($this->runners)) {
            $this->runners = [];
        }
        $this->runners[] = $runner;
        return $this;
    }

    public function addClub(Club $club): Team
    {
        $this->club = $club;
        return $this;
    }

    public function _getOverall(): ?TeamResult
    {
        return $this->team_results[0] ?? null;
    }

    public function _getFullName()
    {
        return $this->team_name;
    }

    public function getMatchedTeam(array $runnerData, ClassEntity $class = null): ?Team
    {
        $bibNumber = $runnerData['bib_number'] ?? null;
        if ($this->bib_number && $this->bib_number == $bibNumber) {
            return $this;
        }
        $teamName = $runnerData['team_name'] ?? null;
        if ($teamName) {
            if ($this->team_name == $teamName) {
                if ($class) {
                    if ($this->class_id == $class->id) {
                        return $this;
                    } else {
                        return null;
                    }
                } else {
                    return $this;
                }
            } else {
                return null;
            }
        } else {
            $msg = "Fields team_name <$teamName> cannot be empty";
            throw new DetailedException($msg);
        }
    }
}
