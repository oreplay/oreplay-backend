<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Lib\UploadHelper;
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
        TeamResultsTable::addBelongsTo($this);
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
        return $q->contain(ClubsTable::name())
            ->contain(ClassesTable::name())
            ->contain(RunnersTable::name(), function (Query $q) {
                return RunnersTable::mainRunnerContain($q);
            })
            ->contain(
                TeamResultsTable::name()
                . '.' . SplitsTable::name()
                . '.' . ControlsTable::name()
                . '.' . ControlTypesTable::name()
            );
    }

    public function matchTeam(array $teamData, ClassEntity $class): Team
    {
        foreach ($this->_getStoredParticipantsInClass() as $team) {
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

    public function createTeamWithResults(array $teamData, ClassEntity $class, UploadHelper $helper): Team
    {
        $helper->getMetrics()->startClubsTime();
        $team = $this->createTeamIfNotExists($helper->getEventId(), $helper->getStageId(), $teamData, $class);
        $helper->getMetrics()->endClubsTime();

        $results = $teamData['team_results'] ?? [];
        foreach ($results as $resultData) {
            $helper->getMetrics()->addOneTeamResultToCounter();
            $team = $this->TeamResults->createTeamResult($resultData, $team, $helper);
        }
        $runners = $teamData['runners'] ?? [];
        foreach ($runners as $runnerData) {
            $nullClass = new ClassEntity();
            $nullClass->id = null;
            $team->addRunner($this->Runners->createRunnerWithResults($runnerData, $nullClass, $helper));
        }

        $helper->getMetrics()->startClubsTime();
        $club = $teamData['club'] ?? null;
        if ($club) {
            $team->addClub($this->Clubs->createIfNotExists($helper->getEventId(), $helper->getStageId(), $club));
        }
        $helper->getMetrics()->endClubsTime();
        $helper->getMetrics()->addToTeamCounter(1);
        return $team;
    }
}
