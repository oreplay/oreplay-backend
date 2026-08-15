<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\Consts\StatusCode;
use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Course;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\StageType;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;

class CourseImporter
{
    private const UNORDERED_STAGE_TYPES = [StageType::SCORE, StageType::RAID];

    private CourseControlsTable $_courseControls;
    private CoursesTable $_courses;
    private UploadHelper $_helper;

    public function __construct(CourseControlsTable $courseControls, CoursesTable $courses, UploadHelper $helper)
    {
        $this->_courseControls = $courseControls;
        $this->_courses = $courses;
        $this->_helper = $helper;
    }

    public function importInto(ClassEntity $class): void
    {
        $course = $class->course ?? null;
        if (!($course instanceof Course) || !$course->id) {
            return;
        }
        $isUnordered = $this->_isUnorderedStage();
        if ($isUnordered) {
            $this->_markAsUnordered($course);
        }
        if (!$this->_carriesTheWholeCourse()) {
            return;
        }
        $stations = $isUnordered
            ? $this->_everyStationPunchedInClass($class)
            : $this->_stationsMostRunnersPunched($class);
        if (!$stations || $course->isSameUploadHash($stations)) {
            return;
        }
        $course->setHash($stations);
        $this->_courseControls->replaceForCourse($course, $stations);
        $this->_courses->saveOrFail($course);
    }

    private function _isUnorderedStage(): bool
    {
        return in_array($this->_helper->getStageTypeId(), self::UNORDERED_STAGE_TYPES, true);
    }

    private function _markAsUnordered(Course $course): void
    {
        // a new course has the field unset rather than false, so only an already stored false skips
        if ($course->is_ordered === false) {
            return;
        }
        $course->is_ordered = false;
        $this->_courses->saveOrFail($course);
    }

    // the splits of a Radiocontrols upload are only the stations with a radio, and a start list has
    // no splits at all, so neither can define the order of the whole course
    private function _carriesTheWholeCourse(): bool
    {
        return !$this->_helper->getChecker()->isIntermediates()
            && !$this->_helper->getChecker()->isStartLists();
    }

    /**
     * A score or raid class has no course order and its runners choose different controls, so the
     * course is the union of what they punched, not the sequence most of them share. The stations
     * are stored ascending: course_controls needs an order_number, is_ordered says it means nothing.
     *
     * @return string[]
     */
    private function _everyStationPunchedInClass(ClassEntity $class): array
    {
        $stations = [];
        foreach ($this->_finishedResultsOf($class) as $result) {
            foreach ($this->_stationsOf($result) as $station) {
                $stations[$station] = $station;
            }
        }
        $stations = array_values($stations);
        sort($stations, SORT_NATURAL);
        return $stations;
    }

    /**
     * @return string[] station numbers in course order
     */
    private function _stationsMostRunnersPunched(ClassEntity $class): array
    {
        $votesBySequence = [];
        foreach ($this->_finishedResultsOf($class) as $result) {
            $sequence = $this->_stationsOf($result);
            if (!$sequence) {
                continue;
            }
            $key = implode('-', $sequence);
            $votesBySequence[$key] = ($votesBySequence[$key] ?? 0) + 1;
        }
        if (!$votesBySequence) {
            return [];
        }
        arsort($votesBySequence);
        return explode('-', (string)array_key_first($votesBySequence));
    }

    /**
     * A relay carries no runners on the class: they hang off each team, one per leg, and their
     * runner_results are where the punches of that leg are. Reading only $class->runners left every
     * relay class without a control list at all.
     *
     * @return RunnerResult[]
     */
    private function _finishedResultsOf(ClassEntity $class): array
    {
        $results = $this->_finishedResultsOfRunners($class->runners ?? []);
        foreach ($class->teams ?? [] as $team) {
            $results = array_merge($results, $this->_finishedResultsOfRunners($team->runners ?? []));
        }
        return $results;
    }

    /**
     * @param Runner[] $runners
     * @return RunnerResult[]
     */
    private function _finishedResultsOfRunners(array $runners): array
    {
        $results = [];
        foreach ($runners as $runner) {
            foreach ($runner->runner_results ?? [] as $result) {
                if ($result->status_code === StatusCode::OK) {
                    $results[] = $result;
                }
            }
        }
        return $results;
    }

    /**
     * @return string[]
     */
    private function _stationsOf(RunnerResult $result): array
    {
        $stations = [];
        foreach ($result->getSplits() as $split) {
            if ($split->station) {
                $stations[] = (string)$split->station;
            }
        }
        return $stations;
    }
}
