<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Event;
use Results\Model\Entity\StageOrder;

class StageOrdersTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    public static function load(): self
    {
        /** @var StageOrdersTable $table */
        $table = parent::load();
        return $table;
    }

    private function _cacheKeyGetAllInStage(string $stageId): string
    {
        return '_getAllInSt3age_'.$stageId;
    }

    public function getAllInStage(string $stageId): ResultSetInterface
    {
        return $this->find()
            ->cache($this->_cacheKeyGetAllInStage($stageId), CacheGrp::DEFAULT)
            ->where(['stage_id' => $stageId])
            ->orderAsc('created')
            ->all();
    }

    public function deleteCache(string $stageId)
    {
        Cache::delete($this->_cacheKeyGetAllInStage($stageId), CacheGrp::DEFAULT);
    }

    public function getDescriptionByOrder(int $stageOrder, string $stageId): ?StageOrder
    {
        /** @var StageOrder $res */
        $res = $this->getAllInStage($stageId)->filter(function (StageOrder $entity) use ($stageOrder) {
            return $entity->stage_order === $stageOrder;
        })->first();
        if (!$res) {
            return null;
        }
        return $res;
    }

    public function getAllCreatingOne(string $srcStageId, string $eventId, string $stageId): ResultSetInterface
    {
        $stages = $this->getAllInStage($stageId);
        $currentStage = $stages->filter(function (StageOrder $entity) use ($srcStageId) {
            return $entity->original_stage_id === $srcStageId;
        });

        if ($currentStage->isEmpty()) {
            /** @var Event $event */
            $event = EventsTable::load()->find()
                ->matching(StagesTable::name(), function ($q) use ($srcStageId) {
                    return $q->where([StagesTable::field('id') => $srcStageId]);
                })
                ->firstOrFail();
            /** @var StageOrder $new */
            $new = $this->fillNewWithUuid([]);
            $new->stage_id = $stageId;
            $new->event_id = $eventId;
            $new->description = $event->description;
            $new->original_stage_id = $srcStageId;
            $new->stage_order = $stages->count() + 1;
            $this->saveOrFail($new);

            $this->deleteCache($stageId);

            return $this->getAllInStage($stageId);
        } else {
            return $stages;
        }
    }
}
