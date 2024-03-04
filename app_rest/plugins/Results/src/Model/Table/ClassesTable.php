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

    public function findByStage(string $eventId, string $stageId)
    {
        return $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
        ]);
    }
}
