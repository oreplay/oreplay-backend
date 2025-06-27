<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;

/**
 * @property EventsTable $Events
 * @property RunnersTable $Runners
 * @property StageTypesTable $StageTypes
 * @property UploadLogsTable $UploadLogs
 */
class StagesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        EventsTable::addHasMany($this);
        RunnersTable::addBelongsTo($this);
        StageTypesTable::addHasMany($this);
        UploadLogsTable::addBelongsTo($this)->setSort([UploadLogsTable::field('created') => 'ASC']);
    }

    public static function load(): self
    {
        /** @var StagesTable $table */
        $table = parent::load();
        return $table;
    }

    public function findByEvent(string $stageId, string $eventId): Query
    {
        return $this->find()
            ->where([$this->_alias . '.id' => $stageId, 'event_id' => $eventId])
            ->contain(StageTypesTable::name());
    }

    public function getByEvent(string $stageId, string $eventId): Stage
    {
        /** @var Stage $res */
        $res = $this->findByEvent($stageId, $eventId)
            ->firstOrFail();
        return $res;
    }

    public function getOrCreateTotalsInEvent(string $eventId): Stage
    {
        /** @var Stage $stage */
        $stage = $this->find()
            ->where([
                'event_id' => $eventId,
                'stage_type_id' => StageType::TOTALS
            ])
            ->contain(StageTypesTable::name())
            ->orderAsc(StageTypesTable::field('created'))
            ->first();
        if ($stage) {
            return $stage;
        }
        /** @var Stage $stage */
        $stage = $this->patchFromNewWithUuid(['description' => '']);
        $stage->event_id = $eventId;
        $stage->stage_type_id = StageType::TOTALS;
        $stage->stage_type = $this->StageTypes->get($stage->stage_type_id);
        /** @var Stage $stage */
        $stage = $this->saveOrFail($stage);
        return $stage;
    }

    public function getStageTypeId(string $stageId): string
    {
        /** @var Stage $stage */
        $stage = $this->get($stageId);
        return $stage->stage_type_id;
    }
}
