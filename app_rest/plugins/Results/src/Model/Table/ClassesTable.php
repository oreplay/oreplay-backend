<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
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

    public function duplicateIfNotExists(string $classId, string $eventId, string $stageId): ClassEntity
    {
        /** @var ClassEntity $class */
        $class = $this->get($classId);
        $classObj = [
            'id' => '',
            'uuid' => $classId,
            'oe_key' => $class->oe_key,
            'short_name' => $class->short_name,
            'long_name' => $class->long_name,
        ];
        return $this->createIfNotExists($eventId, $stageId, $classObj);
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
                // create courses cache table
                // ---
                // class_id
                // order_number
                // station
                // is_intermediate
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

    public function saveOrFailRetrying(ClassEntity $class): EntityInterface
    {
        $maxRetries = 5;
        $attempt = 0;
        do {
            try {
                return $this->getConnection()->transactional(function () use ($class) {
                    return $this->saveOrFail($class);
                });
            } catch (\PDOException $e) {
                if ($e->getCode() === '40001' && $attempt < $maxRetries) {
                    $attempt++;
                    $around100ms = mt_rand(81000, 102000);
                    usleep($around100ms);
                } else {
                    throw $e;
                }
            }
        } while ($attempt < $maxRetries);
    }

}
