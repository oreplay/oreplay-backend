<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\ClassEntity;

/**
 * @property RunnersTable $Runners
 * @property CoursesTable $Courses
 */
class ClassesTable extends AppTable
{
    protected $_entityClass = ClassEntity::class;

    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addBelongsTo($this);
        CoursesTable::addHasMany($this);
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

    public function findByStage(string $eventId, string $stageId)
    {
        return $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
        ])
            ->orderAsc('oe_key');
    }
}
