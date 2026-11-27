<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query\SelectQuery;
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

    public function save(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        return parent::save($entity, $this->_withoutExistenceProbe($options));
    }

    private function _withoutExistenceProbe(array $options): array
    {
        $options['checkExisting'] = false;
        $options['associated'] = $this->_associationsKeepingExistenceProbe();
        return $options;
    }

    private function _associationsKeepingExistenceProbe(): array
    {
        $keepingProbe = [];
        foreach ($this->associations() as $association) {
            $keepingProbe[$association->getName()] = ['checkExisting' => true];
        }
        return $keepingProbe;
    }

    public function findPunchesAtStation(string $eventId, string $stageId, string $station): SelectQuery
    {
        return $this->find()
            ->where([
                $this->_alias . '.event_id' => $eventId,
                $this->_alias . '.stage_id' => $stageId,
                $this->_alias . '.station' => $station,
                $this->_alias . '.reading_time IS NOT' => null,
            ])
            ->where(function (QueryExpression $exp) {
                $isRadioWithoutDownload = $exp->and([])
                    ->notExists($this->_downloadOfTheSameResultAtStation())
                    ->notExists($this->_downloadOfTheSameRunnerAtStation());
                return $exp->or([$this->_alias . '.is_intermediate' => false, $isRadioWithoutDownload]);
            })
            ->orderBy([
                $this->_alias . '.reading_time' => 'DESC',
                $this->_alias . '.created' => 'DESC',
                $this->_alias . '.id' => 'DESC',
            ], true);
    }

    private function _downloadOfTheSameResultAtStation(): SelectQuery
    {
        $radio = $this->_alias;
        return $this->_downloadAtTheStationOf($radio)
            ->where([
                'Downloaded.runner_result_id <=> ' . $radio . '.runner_result_id',
                'Downloaded.team_result_id <=> ' . $radio . '.team_result_id',
            ]);
    }

    private function _downloadOfTheSameRunnerAtStation(): SelectQuery
    {
        $radio = $this->_alias;
        return $this->_downloadAtTheStationOf($radio)
            ->innerJoin(
                ['DownloadedResult' => $this->RunnerResults->getTable()],
                'DownloadedResult.id = Downloaded.runner_result_id'
            )
            ->where(['DownloadedResult.runner_id = ' . $radio . '.runner_id']);
    }

    private function _downloadAtTheStationOf(string $radio): SelectQuery
    {
        return $this->subquery()
            ->select(['Downloaded.id'])
            ->from(['Downloaded' => $this->getTable()])
            ->where([
                'Downloaded.stage_id = ' . $radio . '.stage_id',
                'Downloaded.station = ' . $radio . '.station',
                'Downloaded.is_intermediate' => false,
                'Downloaded.deleted IS' => null,
            ]);
    }

    public function hasRadioPunch(string $stageId, string $station, FrozenTime $readingTime, string $runnerId): bool
    {
        return $this->find()
            ->where([
                $this->_alias . '.stage_id' => $stageId,
                $this->_alias . '.station' => $station,
                $this->_alias . '.reading_time' => $readingTime,
                $this->_alias . '.runner_id' => $runnerId,
                $this->_alias . '.is_intermediate' => true,
            ])
            ->count() > 0;
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

    public function deleteAllByResultIds(string $foreignKey, array $resultIds): int
    {
        if (!$resultIds) {
            return 0;
        }
        return $this->deleteAll([$foreignKey . ' IN' => $resultIds]);
    }

    public function deleteAllIntermediateByResultIds(string $foreignKey, array $resultIds): int
    {
        if (!$resultIds) {
            return 0;
        }
        return $this->deleteAll([$foreignKey . ' IN' => $resultIds, 'is_intermediate' => true]);
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
}
