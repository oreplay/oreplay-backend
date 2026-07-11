<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\Validation\Validator;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageOrder;

class StageOrdersTable extends AppTable
{
    public const int DESCRIPTION_MAX_LENGTH = 255;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('description')
            ->maxLength('description', self::DESCRIPTION_MAX_LENGTH);
        return $validator;
    }

    public static function truncateDescription(?string $description): string
    {
        return mb_substr((string)$description, 0, self::DESCRIPTION_MAX_LENGTH);
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
            ->orderByAsc('created')
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
            /** @var Stage $srcStage */
            $srcStage = StagesTable::load()->find()
                ->where([StagesTable::field('id') => $srcStageId])
                ->contain(EventsTable::name())
                ->firstOrFail();
            /** @var StageOrder $new */
            $new = $this->fillNewWithUuid([]);
            $new->stage_id = $stageId;
            $new->event_id = $eventId;
            $new->description = self::truncateDescription($srcStage->event->description);
            $new->original_stage_id = $srcStageId;
            $new->original_event_id = $srcStage->event_id;
            $new->start = $srcStage->start;
            $new->computed = FrozenTime::now();
            $new->is_official = true;
            $new->stage_order = $stages->count() + 1;
            $this->saveOrFail($new);

            $this->deleteCache($stageId);

            return $this->getAllInStage($stageId);
        } else {
            return $stages;
        }
    }
}
