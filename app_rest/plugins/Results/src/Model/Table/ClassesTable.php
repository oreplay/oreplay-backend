<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Results\Model\Entity\ClassEntity;

/**
 * @property RunnersTable $Runners
 * @property TeamsTable $Teams
 * @property CoursesTable $Courses
 * @property SplitsTable $Splits
 */
class ClassesTable extends AppTable
{
    protected $_entityClass = ClassEntity::class;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addBelongsTo($this);
        TeamsTable::addBelongsTo($this);
        CoursesTable::addHasMany($this);
        SplitsTable::addBelongsTo($this)->setSort(SplitsTable::defaultOrder());
    }

    public static function load(): self
    {
        /** @var ClassesTable $table */
        $table = parent::load();
        return $table;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): ClassEntity
    {
        /** @var ClassEntity $class */
        $class = parent::createIfNotExists($eventId, $stageId, $data);
        return $class;
    }

    public function getByShortName(string $eventId, string $stageId, string $shortName): ?ClassEntity
    {
        /** @var ClassEntity $res */
        $res = parent::getByShortName($eventId, $stageId, $shortName);
        return $res;
    }

    public function getByStageWithRadios(string $eventId, string $stageId)
    {
        $res = $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
        ])
            ->contain(SplitsTable::name(), function (Query $q) {
                $select = [
                    'class_id',
                    'station',
                    'reading_time'  => $q->func()->min(SplitsTable::field('reading_time'), ['string']),
                    'id' => $q->func()->max(SplitsTable::field('id'), ['string']),
                ];
                return $q
                    ->select($select)
                    ->where(['is_intermediate' => true])
                    ->group(['station', 'class_id'])
                    ->order(['station' => 'DESC'], true);
            })
            ->order(['CAST(oe_key AS UNSIGNED)' => 'ASC', 'short_name' => 'ASC'])
            ->all();
        /** @var ClassEntity $r */
        foreach ($res as $r) {
            $r->setSplitsAsSimpleArray();
        }
        return $res;
    }

    public function saveManyWithRelations(ClassEntity $singleClassToSave)
    {
        try {
            return $this->saveManyOrFail([$singleClassToSave]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
