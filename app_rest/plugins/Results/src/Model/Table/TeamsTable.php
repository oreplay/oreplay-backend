<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Team;
use Results\Model\Traits\StoredParticipantTrait;

/**
 * @property RunnersTable $Runners
 * @property ClassesTable $Classes
 * @property ClubsTable $Clubs
 * @property TeamResultsTable $TeamResults
 */
class TeamsTable extends AppTable
{
    use StoredParticipantTrait;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        TeamResultsTable::addBelongsTo($this)->setSort(['time_seconds' => 'DESC']);
        RunnersTable::addBelongsTo($this)->setSort(['leg_number' => 'ASC', 'last_name' => 'ASC']);
        ClassesTable::addHasMany($this);
        ClubsTable::addHasMany($this);
    }

    private function _findTeamsInStage(string $eventId, string $stageId): RestApiSelectQuery
    {
        /** @var RestApiSelectQuery $res */
        $res = $this->find()
            ->where([$this->_alias . '.event_id' => $eventId, $this->_alias . '.stage_id' => $stageId]);
        return $res;
    }

    public function findTeamsInStage(string $eventId, string $stageId, array $filters = []): Query
    {
        $q = $this->_findTeamsInStage($eventId, $stageId);
        if ($filters['class_id'] ?? null) {
            $q->where(['class_id' => $filters['class_id']]);
        }
        if ($filters['club_id'] ?? null) {
            $q->where(['club_id' => $filters['club_id']]);
        }
        if ($filters['text'] ?? null) {
            $q->where([
                'team_name LIKE' => '%'.$filters['text'].'%',
            ]);
        }
        $q = RunnersTable::mainRunnerContain($q, TeamResultsTable::name(), $filters);
        return $q
            ->contain(RunnersTable::name(), function (Query $q) use ($filters) {
                return RunnersTable::mainRunnerContain($q, RunnerResultsTable::name(), $filters);
            });
    }

    public function matchTeam(array $teamData, ClassEntity $class): Team
    {
        $candidates = array_merge(
            $this->_candidatesFor('bib', $teamData['bib_number'] ?? null),
            $this->_candidatesFor('name', $teamData['team_name'] ?? null)
        );
        foreach ($candidates as $team) {
            $matchedTeam = $team->getMatchedTeam($teamData, $class);
            if ($matchedTeam) {
                return $matchedTeam;
            }
        }
        if ($teamData['bib_number'] ?? null) {
            throw new NotFoundException('Not found team by bib_number');
        }
        throw new NotFoundException('Not found team by name');
    }

    public function createTeamIfNotExists(
        string $eventId,
        string $stageId,
        array $teamData,
        ClassEntity $class
    ): Team {
        $this->getStoredAllParticipantsInClass($eventId, $stageId, $class->id);
        try {
            $team = $this->matchTeam($teamData, $class);
        } catch (NotFoundException $e) {
            /** @var Team $team */
            $team = $this->fillNewWithStage($teamData, $eventId, $stageId);
            $this->addParticipantInClass($team, $class->id);
        }
        return $team;
    }
}
