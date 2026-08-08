<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Split;

/**
 * @property RunnerResultsTable $RunnerResults
 * @property TeamResultsTable $TeamResults
 * @property ControlsTable $Controls
 */
class SplitsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnerResultsTable::addHasMany($this);
        TeamResultsTable::addHasMany($this);
        ControlsTable::addHasMany($this);
    }

    public static function load(): self
    {
        /** @var SplitsTable $table */
        $table = parent::load();
        return $table;
    }

    public static function defaultOrder(): array
    {
        return ['reading_time' => 'ASC'];
    }

    protected function _insert(EntityInterface $entity, array $data): EntityInterface|false
    {
        return parent::_insert($entity, $data);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId)
    {
        /** @var Split $split */
        $split = parent::fillNewWithStage($data, $eventId, $stageId);
        return $split;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Split
    {
        $conditions = $data;
        unset($conditions['time_seconds']);

        $cacheKey = 'createIfNotExists_' . $this->_alias . '_' . UploadHelper::md5Encode($conditions);
        /** @var Split $entity */
        $entity = $this->getFromCache([$cacheKey, $conditions]);
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $eventId, $stageId);
        }
        return $entity;
    }

    public function deleteAllByRunnerResultId(string $runnerId): int
    {
        return $this->deleteAll(['runner_result_id' => $runnerId]);
    }

    /**
     * @param string[] $splitIds
     * @return int
     */
    public function softDeleteMany(array $splitIds): int
    {
        if (!$splitIds) {
            return 0;
        }
        return $this->updateAll(['deleted' => new FrozenTime()], ['id in' => $splitIds]);
    }

    public function getStationsFromLeaderInStage(string $eventId, string $stageId): array
    {
        $cacheKey = 'getStationsFromLeaderInStage' . $stageId;
        $res = Cache::read($cacheKey, CacheGrp::SHORT);
        if ($res) {
            return $res;
        }
        $splits = $this->find()
            ->where([
                SplitsTable::field('event_id') => $eventId,
                SplitsTable::field('stage_id') => $stageId,
                'is_intermediate' => 0,
                ])
            ->matching(RunnerResultsTable::name(), function ($q) use ($eventId, $stageId) {
                return $q->where([
                    RunnerResultsTable::field('event_id') => $eventId,
                    RunnerResultsTable::field('stage_id') => $stageId,
                    RunnerResultsTable::field('position') => 1,
                ]);
            })
            ->orderBy(['reading_time' => 'ASC'])
            ->all();
        $stations = [];
        /** @var Split $split */
        foreach ($splits as $split) {
            $stations[$split->class_id][] = $split->station;
        }
        Cache::write($cacheKey, $stations, CacheGrp::SHORT);
        return $stations;
    }
}
