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

    /**
     * The course a class's own row points at, which is what the classes endpoint draws its table from.
     *
     * Normally that is the course block the payload puts on the class. When the runners declare their
     * own courses the class block names one variant among several, so pointing the class at it would
     * show one variant's controls to everybody; the class gets a course of its own instead, carrying
     * the controls every variant shares. See upload-courses.md 8.2.
     */
    public function createForClassIfNotExists(string $eventId, string $stageId, array $classArray): ?Course
    {
        $courseArray = $classArray['course'] ?? [];
        if (!$courseArray) {
            return null;
        }
        if (count(self::_courseNamesTheRunnersDeclare($classArray)) > 1) {
            $courseArray = [
                'oe_key' => $classArray['oe_key'] ?? '',
                'short_name' => $classArray['short_name'] ?? '',
            ];
        }
        return $this->createIfNotExists($eventId, $stageId, $courseArray);
    }

    /**
     * More than one means the class forks. Every runner naming the *same* course is the ordinary
     * case and needs no course of its own for the class.
     *
     * @return string[]
     */
    private static function _courseNamesTheRunnersDeclare(array $classArray): array
    {
        $runners = $classArray['runners'] ?? [];
        foreach ($classArray['teams'] ?? [] as $team) {
            $runners = array_merge($runners, $team['runners'] ?? []);
        }
        $names = [];
        foreach ($runners as $runner) {
            $shortName = $runner['course']['short_name'] ?? null;
            if ($shortName) {
                $names[$shortName] = $shortName;
            }
        }
        return array_values($names);
    }
}
