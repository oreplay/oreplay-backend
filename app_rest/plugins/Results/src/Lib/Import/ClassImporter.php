<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamsTable;

/**
 * Imports one class and commits it, which is the boundary the upload is reported and published at.
 *
 * v2 only. v1 keeps its own copy of this loop, deliberately untouched, see docs/uploads-v1-vs-v2.md.
 */
class ClassImporter
{
    public function __construct(
        private readonly ClassesTable $classes,
        private readonly UploadHelper $helper
    ) {
    }

    public function import(array $classArray, ClassEntity $class): ClassImportReport
    {
        $class->setHash($classArray);
        $class = $this->_addCourseToClass($classArray, $class);
        $classHelper = $this->helper->inClass($class->id)->runningCourse($class->course->id ?? '');
        $class = $this->_addAllRunnersInClass($classArray, $class, $classHelper);
        $class = $this->_addAllTeamsInClass($classArray, $class, $classHelper);
        $this->helper->getMetrics()->saveManyOrFail(
            $this->classes,
            $class,
            $this->helper->getSplitsToReplace(),
            $this->helper->getRowsToInsert()
        );
        $this->_storeCourseOf($class);
        return ClassImportReport::of($class);
    }

    private function _addCourseToClass(array $classArray, ClassEntity $class): ClassEntity
    {
        $metrics = $this->helper->getMetrics();
        $course = $metrics->measure(
            UploadMetrics::COURSES,
            fn() => $this->classes->Courses->createForClassIfNotExists(
                $this->helper->getEventId(),
                $this->helper->getStageId(),
                $classArray
            )
        );
        if (!$course) {
            return $class;
        }
        $class->course = $course;
        $metrics->addCourse($course);
        return $class;
    }

    private function _storeCourseOf(ClassEntity $class): void
    {
        $importer = new CourseImporter(CourseControlsTable::load(), CoursesTable::load(), $this->helper);
        $importer->importInto($class);
    }

    private function _addAllRunnersInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->_runnersTable()->ifDifferentClassEmptyStoredList($class->id);
        $importer = new RunnerImporter($this->_runnersTable(), $helper);
        $runnerArray = $classArray['runners'] ?? [];
        $runners = $helper->getMetrics()->measure(
            UploadMetrics::PARTICIPANTS_LOOP,
            fn() => $this->_importEachRunner($runnerArray, $class, $importer, $helper)
        );
        $class->addRunners($runners);
        return $class;
    }

    /**
     * @return Runner[]
     */
    private function _importEachRunner(
        array $runnerArray,
        ClassEntity $class,
        RunnerImporter $importer,
        UploadHelper $helper
    ): array {
        $metrics = $helper->getMetrics();
        $runners = [];
        $existingRunnerIDs = [];
        foreach ($runnerArray as $runnerData) {
            $runner = $metrics->measure(
                UploadMetrics::PARTICIPANTS_IN_LOOP,
                fn() => $importer->import($runnerData, $class)
            );
            if (in_array($runner->id, $existingRunnerIDs)) {
                // warning: two payload entries can match one runner (same bib, or same name in the class,
                // see Runner::getMatchedRunner) so two real people silently become one row and one set of
                // results. Dropping the duplicate here only avoids saving it twice; the merge still happens.
                $metrics->setDataLossWarning(
                    'Duplicated runner ' . $runner->_getFullName() . ' ' . $runner->bib_number,
                    UploadMetrics::CODE_DUPLICATED_RUNNER,
                    ['class' => $class->short_name, 'bib' => $runner->bib_number]
                );
                continue;
            }
            $existingRunnerIDs[] = $runner->id;
            $runners[] = $runner;
        }
        return $runners;
    }

    private function _addAllTeamsInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->_teamsTable()->ifDifferentClassEmptyStoredList($class->id);
        $importer = new TeamImporter($this->_teamsTable(), $helper);
        $teamArray = $classArray['teams'] ?? [];
        $class->teams = $helper->getMetrics()->measure(
            UploadMetrics::PARTICIPANTS_LOOP,
            fn() => $this->_importEachTeam($teamArray, $class, $importer, $helper)
        );
        return $class;
    }

    /**
     * @return Team[]
     */
    private function _importEachTeam(
        array $teamArray,
        ClassEntity $class,
        TeamImporter $importer,
        UploadHelper $helper
    ): array {
        $metrics = $helper->getMetrics();
        $teams = [];
        foreach ($teamArray as $teamData) {
            // warning: unlike _importEachRunner() this has no duplicate detection, so two teams
            // sharing a bib (see Team::getMatchedRunner) are matched as one team and appended twice.
            // No upload example carries duplicated team bibs, so nothing exercises it.
            $teams[] = $metrics->measure(
                UploadMetrics::PARTICIPANTS_IN_LOOP,
                fn() => $importer->import($teamData, $class)
            );
        }
        return $teams;
    }

    private function _teamsTable(): TeamsTable
    {
        return $this->classes->Teams->getTarget();
    }

    private function _runnersTable(): RunnersTable
    {
        return $this->classes->Runners->getTarget();
    }
}
