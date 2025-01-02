<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Traits\StoredParticipantTrait;

/**
 * @property UsersTable $Users
 * @property RunnerResultsTable $RunnerResults
 * @property StagesTable $Stages
 * @property ClassesTable $Classes
 * @property ClubsTable $Clubs
 * @property TeamsTable $Teams
 */
class RunnersTable extends AppTable
{
    use StoredParticipantTrait;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addBelongsTo($this);
        StagesTable::addHasMany($this);
        UsersTable::addHasMany($this);
        ClassesTable::addHasMany($this);
        ClubsTable::addHasMany($this);
        TeamsTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var RunnersTable $table */
        $table = parent::load();
        return $table;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Runner
    {
        /** @var Runner $class */
        $class = parent::createIfNotExists($eventId, $stageId, $data);
        return $class;
    }

    private function _findRunnersInStageBy(
        string $field,
        string $eventId,
        string $stageId,
        array $runnerData
    ): ?Runner {
        /** @var Runner $potentialRunner */
        $q = $this->_findRunnersInStage($eventId, $stageId);
        if ($runnerData[$field] ?? null) {
            $q->where([$field => $runnerData[$field]]);
            $potentialRunner = $q->first();
            if ($potentialRunner) {
                return $potentialRunner;
            } else {
                throw new NotFoundException('Not found runner by ' . $field);
            }
        }
        return null;
    }

    public function matchRunner(array $runnerData, ClassEntity $class): Runner
    {
        foreach ($this->_getStoredParticipantsInClass() as $runner) {
            $matchedRunner = $runner->getMatchedRunner($runnerData, $class);
            if ($matchedRunner) {
                return $matchedRunner;
            }
        }
        if ($runnerData['db_id'] ?? null) {
            throw new NotFoundException('Not found runner by db_id');
        }
        if ($runnerData['bib_number'] ?? null) {
            throw new NotFoundException('Not found runner by bib_number');
        }
        //foreach ($this->_getStoredRunnersInClass() as $runner) {
        //    $matchedRunner = $runner->getMatchedRunnerWithoutSportIdent($runnerData, $class);
        //    if ($matchedRunner) {
        //        return $matchedRunner;
        //    }
        //}
        throw new NotFoundException('Not found runner by name');
    }

    public function createRunnerIfNotExists(
        string $eventId,
        string $stageId,
        array $runnerData,
        ClassEntity $class
    ): Runner {
        $this->getStoredAllParticipantsInClass($eventId, $stageId, $class->id);
        try {
            $runner = $this->matchRunner($runnerData, $class);
        } catch (NotFoundException $e) {
            /** @var Runner $runner */
            $runner = $this->fillNewWithStage($runnerData, $eventId, $stageId);
            $this->addParticipantInClass($runner, $class->id);
        }
        return $runner;
    }

    private function _findRunnersInStage(string $eventId, string $stageId): RestApiSelectQuery
    {
        /** @var RestApiSelectQuery $res */
        $res = $this->find()
            ->where([$this->_alias . '.event_id' => $eventId, $this->_alias . '.stage_id' => $stageId]);
        return $res;
    }

    public function findRunnersInStage(string $eventId, string $stageId, array $filters = []): Query
    {
        $q = $this->_findRunnersInStage($eventId, $stageId);
        if ($filters['class_id'] ?? null) {
            $q->where(['class_id' => $filters['class_id']]);
        }
        if ($filters['club_id'] ?? null) {
            $q->where(['club_id' => $filters['club_id']]);
        }
        return $q->contain(ClubsTable::name())
            ->contain(ClassesTable::name())
            ->contain(
                RunnerResultsTable::name()
                . '.' . SplitsTable::name()
                . '.' . ControlsTable::name()
                . '.' . ControlTypesTable::name()
            );
    }

    public function createRunnerWithResults(array $runnerData, ClassEntity $class, UploadHelper $helper): Runner
    {
        $helper->getMetrics()->startClubsTime();
        $runner = $this->createRunnerIfNotExists($helper->getEventId(), $helper->getStageId(), $runnerData, $class);
        $helper->getMetrics()->endClubsTime();

        $results = $runnerData['runner_results'] ?? [];
        foreach ($results as $resultData) {
            $helper->getMetrics()->addOneRunnerToCounter();
            $runner = $this->RunnerResults->createRunnerResult($resultData, $runner, $helper);
        }

        $helper->getMetrics()->startClubsTime();
        $runnerClub = $runnerData['club'] ?? null;
        if ($runnerClub) {
            $runner->club = $this->Clubs->createIfNotExists($helper->getEventId(), $helper->getStageId(), $runnerClub);
        }
        $helper->getMetrics()->endClubsTime();
        return $runner;
    }
}
