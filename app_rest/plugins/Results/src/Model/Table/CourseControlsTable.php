<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Behavior\TimestampBehavior;
use Results\Model\Entity\Course;
use Results\Model\Entity\CourseControl;

/**
 * @property CoursesTable $Courses
 * @property ControlsTable $Controls
 */
class CourseControlsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        CoursesTable::addBelongsTo($this);
        ControlsTable::addBelongsTo($this);
    }

    public static function load(): self
    {
        /** @var CourseControlsTable $table */
        $table = parent::load();
        return $table;
    }

    /**
     * @param array $stations station numbers in course order, first one is order_number 1
     */
    public function replaceForCourse(Course $course, array $stations): int
    {
        $this->deleteAll(['course_id' => $course->id]);
        $toSave = [];
        foreach (array_values($stations) as $index => $station) {
            $control = $this->fillNewWithStage(
                ['station' => (string)$station, 'order_number' => $index + 1],
                $course->event_id,
                $course->stage_id
            );
            $control->course_id = $course->id;
            $toSave[] = $control;
        }
        if ($toSave) {
            $this->saveManyOrFail($toSave);
        }
        return count($toSave);
    }

    public function findByCourse(string $courseId): array
    {
        /** @var CourseControl[] $res */
        $res = $this->find()
            ->where([CourseControlsTable::field('course_id') => $courseId])
            ->orderByAsc(CourseControlsTable::field('order_number'))
            ->all()->toList();
        return $res;
    }
}
