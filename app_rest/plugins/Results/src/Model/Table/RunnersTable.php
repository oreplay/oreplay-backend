<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Rankings\Model\Table\ParticipantInterface;
use RestApi\Model\ORM\RestApiSelectQuery;
use Results\Lib\ResultsSorter;
use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
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
        RunnerResultsTable::addBelongsTo($this)->setSort(['time_seconds' => 'DESC']);
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

    public function findByCard($siCard, $eventId, $stageId): Query
    {
        return $this->find()
            ->where([
                'sicard' => $siCard,
                'event_id' => $eventId,
                'stage_id' => $stageId,
            ])
            ->contain(RunnerResultsTable::name())
            ->order(['modified' => 'DESC']);
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
            $this->addParticipantInClass($runner, $class->id ?: '');
        }
        return $runner;
    }

    public function duplicateIfNotExists(
        string $eventId,
        string $stageId,
        ParticipantInterface $participant,
        ClassEntity $class
    ): Runner {
        $runnerData = $participant->toArrayWithoutID();
        return $this->createRunnerIfNotExists($eventId, $stageId, $runnerData, $class);
    }

    private function _findRunnersInStage(string $eventId, string $stageId): RestApiSelectQuery
    {
        /** @var RestApiSelectQuery $res */
        $res = $this->find()
            ->where([$this->_alias . '.event_id' => $eventId, $this->_alias . '.stage_id' => $stageId]);
        return $res;
    }

    public static function sortTotals(array $toRet): array
    {
        usort($toRet, ResultsSorter::sortTotals());

        /** @var Team|Runner $value */
        foreach ($toRet as $i => $value) {
            $value->addPositionIfNeeded($i + 1);
        }
        return $toRet;
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
        if ($filters['text'] ?? null) {
            $q->where([
                'OR' => [
                    'first_name LIKE' => '%'.$filters['text'].'%',
                    'last_name like' => '%'.$filters['text'] .'%',
                    'sicard' => $filters['text'],
                    'bib_number' => $filters['text']
                ]
            ]);
        }
        return $this->mainRunnerContain($q, RunnerResultsTable::name(), $filters);
    }

    public static function mainRunnerContain($q, string $resultsTableName, array $filters = [])
    {
        return $q->contain(ClubsTable::name())
            ->contain(ClassesTable::name())
            ->contain($resultsTableName, function (Query $q) use ($filters) {
                return $q->contain(SplitsTable::name(), function (Query $q) use ($filters) {
                    $order = [
                        //'order_number' => 'DESC', // order number will change from splits or radios export
                        'reading_time' => 'DESC', // 1st punch time (latest first, null last)
                        'is_intermediate' => 'ASC', // 2nd no radio before radio
                        SplitsTable::field('created') => 'DESC' // 4th db created
                    ];
                    if ($filters['forceSameDay'] ?? null) {
                        // station order should be reverted when fixing jwlNAilf/197
                        $order = array_merge(
                            [SplitsTable::field('station') => 'ASC'], // 1st exclude bad days on reading times
                            $order
                        );
                    }
                    $station = $filters['station'] ?? null;
                    if ($station) {
                        if (is_array($station)) {
                            $in = ' in';
                        } else {
                            $in = '';
                        }
                        $q->where([SplitsTable::field('station') . $in => $station]);
                    }
                    return $q
                        ->order($order, true)
                        ->contain(ControlsTable::name(), function (Query $q) {
                            return $q->contain(ControlTypesTable::name());
                        });
                });
            });
    }

    public function createRunnerWithResults(array $runnerData, ClassEntity $class, UploadHelper $helper): Runner
    {
        if (!isset($runnerData['leg_number']) && isset($runnerData['runner_results'][0]['leg_number'])) {
            $runnerData['leg_number'] = $runnerData['runner_results'][0]['leg_number'];
        }
        $helper->getMetrics()->startClubsTime();
        $runner = $this->createRunnerIfNotExists($helper->getEventId(), $helper->getStageId(), $runnerData, $class);
        $helper->getMetrics()->endClubsTime();

        $results = $runnerData['runner_results'] ?? [];
        if (!$results) {
            $helper->getMetrics()->setWarning('Runner without runner_results');
        }
        foreach ($results as $resultData) {
            $helper->getMetrics()->addOneRunnerResultToCounter();
            $runner = $this->RunnerResults->createRunnerResult($resultData, $runner, $helper);
        }

        $helper->getMetrics()->startClubsTime();
        $club = $runnerData['club'] ?? null;
        if ($club) {
            $runner->addClub($this->Clubs->createIfNotExists($helper->getEventId(), $helper->getStageId(), $club));
        }
        $helper->getMetrics()->endClubsTime();
        $helper->getMetrics()->addToRunnerCounter(1);
        return $runner;
    }
}
