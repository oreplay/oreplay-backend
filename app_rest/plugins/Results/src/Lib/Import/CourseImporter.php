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
        if (!$isUnordered) {
            $this->_storeDeclaredVariants($class);
        }
        $this->_storeCourse($course, $isUnordered
            ? $this->_everyStationPunchedInClass($class)
            : $this->_commonCourseOf($class));
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
     * The controls every finisher of the class passed, in an order they all agree on.
     *
     * For an ordinary class every runner ran the same thing, so this is that course. Where a class
     * forks — relay legs, butterflies, Motala — no single runner's sequence is the class's course,
     * and the part they share is what can be compared and drawn as columns. Controls only some
     * runners visited stay in `splits`, where the per-runner ticket needs them.
     *
     * It is a longest common subsequence rather than a set intersection, because the answer has to
     * be an order and it has to keep repeats: an arena control passed twice by everyone is two
     * columns, and a set would collapse it to one.
     *
     * @return string[]
     */
    private function _commonCourseOf(ClassEntity $class): array
    {
        return $this->_commonOf($this->_distinctSequencesOf($this->_finishedResultsOf($class)));
    }

    /**
     * @param RunnerResult[] $results
     * @return array<string, string[]>
     */
    private function _distinctSequencesOf(array $results): array
    {
        $sequences = [];
        foreach ($results as $result) {
            $stations = $this->_stationsOf($result);
            if ($stations) {
                $sequences[implode('-', $stations)] = $stations;
            }
        }
        return $sequences;
    }

    /**
     * @param string[][] $sequences
     * @return string[]
     */
    private function _commonOf(array $sequences): array
    {
        $sequences = array_values($sequences);
        $common = array_shift($sequences);
        foreach ($sequences as $sequence) {
            $common = $this->_longestCommonSubsequence($common, $sequence);
        }
        return $common ?: [];
    }

    /**
     * Each variant the runners declared gets its own control list, so a runner can be measured
     * against the course actually assigned to them. The class keeps the common course, which is what
     * the classes endpoint draws its table from.
     */
    private function _storeDeclaredVariants(ClassEntity $class): void
    {
        $resultsByCourse = [];
        foreach ($this->_finishedResultsOf($class) as $result) {
            if ($result->course_id && $result->course_id !== ($class->course->id ?? null)) {
                $resultsByCourse[$result->course_id][] = $result;
            }
        }
        foreach ($resultsByCourse as $courseId => $results) {
            $this->_storeCourse($this->_courses->get($courseId), $this->_commonOf(
                $this->_distinctSequencesOf($results)
            ));
        }
    }

    private function _storeCourse(Course $course, array $stations): void
    {
        if (!$stations || $course->isSameUploadHash($stations)) {
            return;
        }
        $course->setHash($stations);
        $this->_courseControls->replaceForCourse($course, $stations);
        $this->_courses->saveOrFail($course);
    }

    /**
     * @param string[] $first
     * @param string[] $second
     * @return string[]
     */
    private function _longestCommonSubsequence(array $first, array $second): array
    {
        $firstLength = count($first);
        $secondLength = count($second);
        $lengths = array_fill(0, $firstLength + 1, array_fill(0, $secondLength + 1, 0));
        for ($i = 1; $i <= $firstLength; $i++) {
            for ($j = 1; $j <= $secondLength; $j++) {
                $lengths[$i][$j] = $first[$i - 1] === $second[$j - 1]
                    ? $lengths[$i - 1][$j - 1] + 1
                    : max($lengths[$i - 1][$j], $lengths[$i][$j - 1]);
            }
        }
        $common = [];
        $i = $firstLength;
        $j = $secondLength;
        while ($i > 0 && $j > 0) {
            if ($first[$i - 1] === $second[$j - 1]) {
                array_unshift($common, $first[$i - 1]);
                $i--;
                $j--;
            } elseif ($lengths[$i - 1][$j] >= $lengths[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }
        return $common;
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
