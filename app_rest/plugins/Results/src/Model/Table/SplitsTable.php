<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
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
