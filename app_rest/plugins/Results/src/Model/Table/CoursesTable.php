<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Course;

/**
 * @property ClassesTable $Runner
 */
class CoursesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        ClassesTable::addBelongsTo($this);
    }

    public static function load(): self
    {
        /** @var CoursesTable $table */
        $table = parent::load();
        return $table;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Course
    {
        /** @var Course $class */
        $class = parent::createIfNotExists($eventId, $stageId, $data);
        return $class;
    }
}
