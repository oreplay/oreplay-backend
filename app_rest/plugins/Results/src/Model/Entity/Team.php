<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Rankings\Model\Table\ParticipantInterface;
use Rankings\Model\Traits\ParticipantTrait;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\ResultsFilter;

/**
 * @property string $team_name
 * @property mixed $bib_number
 * @property string $class_id
 * @property bool $is_nc
 * @property TeamResult[] $team_results
 */
class Team extends AppEntity implements ParticipantInterface
{
    use ParticipantTrait;

    public const FIRST_TEAM = '8ea9f351-4141-4ff2-891d-9e2a904bc296';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'team_name' => true,
        'bib_number' => true,
        'is_nc' => true,
        'eligibility' => true,
    ];

    protected $_virtual = [
        'full_name',
        'stage',
        'overalls',
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

    /**
     * @param TeamResult[] $existingResults
     * @return $this
     */
    public function removeAllExistingResults(array $existingResults): static
    {
        foreach ($existingResults as $existingResult) {
            $existingResult->setSoftDeleted();
            $this->addTeamResult($existingResult);
        }
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
        $this->_fields['club'] = $club;
        return $this;
    }

    public function _getClub(): ?Club
    {
        return $this->_fields['club'] ?? null;
    }

    /**
     * @return TeamResult[]
     */
    public function getResultList()
    {
        return $this->team_results;
    }

    public function _getStage(): ?TeamResult
    {
        /** @var TeamResult $res */
        $res = ResultsFilter::getFirstStage($this->getResultList());
        if ($res) {
            $res->cleanSplitsWithoutRadios();
        }
        return $res;
    }

    public function _getFullName()
    {
        return $this->team_name;
    }

    public function getMatchedTeam(array $runnerData, ClassEntity $class = null): ?Team
    {
        if ($this->isSameField('bib_number', $runnerData)) {
            return $this;
        }
        $teamName = $runnerData['team_name'] ?? null;
        if ($teamName) {
            if ($this->team_name == $teamName) {
                return $this->isSameClass($class);
            } else {
                return null;
            }
        } else {
            $msg = "Fields team_name <$teamName> cannot be empty";
            throw new DetailedException($msg);
        }
    }

    public function toArrayWithoutID(): array
    {
        $participant = $this->jsonSerialize();
        $participant['id'] = '';
        $participant['team_name'] = $this->team_name;
        return $participant;
    }
}
