<?php

declare(strict_types = 1);

namespace Rankings\Model\Table;

use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Rankings\Lib\RankingUploadConfigChecker;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Model\Entity\Ranking;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StageOrdersTable;
use Results\Model\Table\StagesTable;

/**
 * @property StagesTable $Stages
 */
class RankingsTable extends AppTable
{
    public const FIRST_RANKING = 'regional100pts';

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        StagesTable::addBelongsToMany($this);
    }

    public static function load(): self
    {
        /** @var RankingsTable $table */
        $table = parent::load();
        return $table;
    }

    public function getCached(string $rankingId): Ranking
    {
        $cacheKey = '_getRankingSettings_' . $rankingId;
        $res = Cache::read($cacheKey);
        if ($res) {
            return $res;
        }
        /** @var Ranking $res */
        $res = $this->find()
            ->where(['id' => $rankingId])
            ->firstOrFail();
        Cache::write($cacheKey, $res);
        return $res;
    }

    public static function getCalculator(string $rankingId): ScoringAlgorithm
    {
        $settings = RankingsTable::load()->getCached($rankingId);
        return new SimpleScoreCalculator($settings);
    }

    public function saveAsOrganizer(Ranking $rk, string $runnerId, string $classId, int $stageOrder): RunnerResult
    {
        $resultData = [
            'stage_order' => $stageOrder,
            'note' => 'ORG',
            'position' => null,
            'points_final' => null,
            'time_seconds' => null,
            'status_code' => StatusCode::OK,
        ];

        $RunnerResults = RunnerResultsTable::load();
        $runnerResult = $RunnerResults->fillNewWithStage($resultData, $rk->getEventId(), $rk->getStageId());
        $runnerResult->upload_type = UploadTypes::COMPUTABLE_ORGANIZER;
        $runnerResult->class_id = $classId;
        $runnerResult->runner_id = $runnerId;
        $runnerResult->result_type_id = ResultType::PARTIAL_OVERALL;
        /** @var RunnerResult $res */
        $res = $RunnerResults->saveOrFail($runnerResult);
        return $res;
    }

    public function saveRanking(
        string $rankingName,
        string $srcStageId,
        string $classId,
        array $participants
    ): ?EntityInterface {
        /** @var ParticipantInterface $first */
        $first = $participants[0];
        if ($first->isLeader()) {
            $rk = $this->getCached($rankingName);

            $classesTable = ClassesTable::load();
            $class = $classesTable->duplicateIfNotExists($classId, $rk->getEventId(), $rk->getStageId());
            $stages = StageOrdersTable::load()->getAllCreatingOne($srcStageId, $rk->getEventId(), $rk->getStageId());
            $runners = [];

            $clubTable = ClubsTable::load();

            /** @var ParticipantInterface $participant */
            foreach ($participants as $participant) {
                $participant->setSettings($rk);
                $participant->setLeader($first);

                $resultData = [
                    'id' => '',
                    'stage_order' => $stages->count(),
                    'position' => $participant->_getStage()->position,
                    'points_final' => $participant->_getRankingPoints(),
                    'time_seconds' => null,
                    'status_code' => StatusCode::OK,
                    'result_type' => [
                        'id' => ResultType::PARTIAL_OVERALL,
                    ]
                ];

                $Runners = RunnersTable::load();
                $runner = $Runners->duplicateIfNotExists($rk->getEventId(), $rk->getStageId(), $participant, $class);
                $runner = $Runners->RunnerResults
                    ->createSimpleRunnerResult($resultData, $runner, $this->_getHelper($rk, $class));

                if ($participant->_getClub()) {
                    $club = $participant->_getClub()->toArray();
                    unset($club['id']);
                    $runner->addClub($clubTable->createIfNotExists($rk->getEventId(), $rk->getStageId(), $club));
                }
                $runners[] = $runner;
            }
            $class->addRunners($runners);
            return $classesTable->saveOrFailRetrying($class);
        } else {
            return null;
        }
    }

    private function _getHelper(Ranking $rk, ClassEntity $class): UploadHelper
    {
        $data['event']['stages'][0]['id'] = $rk->getEventId();
        $uploadHelper = new UploadHelper([
            'oreplay_data_transfer' => $data
        ], $rk->getEventId());
        $uploadHelper->setCurrentClassId($class->id);
        $checker = new RankingUploadConfigChecker($rk);
        $uploadHelper->setConfigChecker($checker);
        return $uploadHelper;
    }

    /**
     * @param string $eventId
     * @param string $stageId
     * @param string $rankingId
     * @return ClassEntity[]
     */
    public function getClassIds(string $eventId, string $stageId, string $rankingId): array
    {
        $config = $this->getCached($rankingId);
        $res = ClassesTable::load()
            ->find()
            ->where(['event_id' => $eventId, 'stage_id' => $stageId])
            ->all()->toArray();
        $excludedCategoryNames = $config->getExcludedClassNames();
        return array_filter($res, function ($class) use ($excludedCategoryNames) {
            return !in_array($class->short_name, $excludedCategoryNames, true);
        });
    }
}
