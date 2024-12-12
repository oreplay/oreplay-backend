<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\I18n\FrozenTime;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\ClassesTable;

class UploadMetrics
{
    private array $_classesToSave = [];
    private int $classCount = 0;
    private int $runnerCount = 0;
    private int $splitAmount = 0;
    private int $runnerResultsCount = 0;
    private int $coursesCount = 0;
    private float $_startTimeTotal = 0.0;
    private float $_startTimeProcessing = 0.0;
    private float $_processingDuration = 0.0;
    private float $_savingDuration = 0.0;
    private float $_totalDuration = 0.0;
    private float $_splitDuration = 0.0;
    private float $_runnerResultsDuration = 0.0;
    private float $_startsTimeSplits = 0.0;
    private float $_startRunnerTime = 0.0;
    private float $_startsCoursesTime = 0.0;
    private float $_coursesDuration = 0.0;
    private float $_startClubsTime = 0.0;
    private float $_clubsDuration = 0.0;
    private float $_startRunnerOutLoopTime = 0.0;
    private float $_runnersOutLoopDuration = 0.0;
    private float $_startRunnerInLoopTime = 0.0;
    private float $_runnersInLoopDuration = 0.0;

    public function __construct()
    {
        $this->startTotal();
        $this->startProcessing();
    }

    private function _roundUp(float $value, int $precision = 2): float
    {
        return $value;
    }
    private function startTotal()
    {
        $this->_startTimeTotal = microtime(true);
    }
    private function startProcessing()
    {
        $this->_startTimeProcessing = microtime(true);
    }

    private function endProcessing()
    {
        $this->_processingDuration += $this->_roundUp(microtime(true) - $this->_startTimeProcessing);
    }

    /**
     * @return ClassEntity[]
     */
    public function saveManyOrFail(ClassesTable $classes, ClassEntity $singleClassToSave): array
    {
        $this->addToRunnerCounter(count($singleClassToSave->runners));
        $this->_classesToSave[] = $singleClassToSave;
        $this->classCount = count($this->_classesToSave);
        $this->endProcessing();

        $startTimeSaving = microtime(true);
        $classes->saveManyWithRelations($singleClassToSave);
        $end = microtime(true);
        $this->_savingDuration += $this->_roundUp($end - $startTimeSaving);
        $this->startProcessing();
        return $this->_classesToSave;
    }
    public function endTotalTimer()
    {
        $end = microtime(true);
        $this->_totalDuration = $this->_roundUp($end - $this->_startTimeTotal);
    }

    private function addToRunnerCounter(int $toAdd)
    {
        $this->runnerCount += $toAdd;
    }

    public function startSplitsTime()
    {
        $this->_startsTimeSplits = microtime(true);
    }

    public function startRunnerResultsTime()
    {
        $this->_startRunnerTime = microtime(true);
    }
    public function endRunnerResultsTime()
    {
        $this->_runnerResultsDuration += round(microtime(true) - $this->_startRunnerTime, 2);
    }

    public function startCoursesTime()
    {
        $this->_startsCoursesTime = microtime(true);
    }
    public function endCoursesTime()
    {
        $this->_coursesDuration += round(microtime(true) - $this->_startsCoursesTime, 2);
        $this->coursesCount++;
    }

    public function startRunnersOutLoopTime()
    {
        $this->_startRunnerOutLoopTime = microtime(true);
    }

    public function endRunnersOutLoopTime()
    {
        $this->_runnersOutLoopDuration += round(microtime(true) - $this->_startRunnerOutLoopTime, 2);
    }

    public function startRunnersInLoopTime()
    {
        $this->_startRunnerInLoopTime = microtime(true);
    }
    public function endRunnersInLoopTime()
    {
        $this->_runnersInLoopDuration += round(microtime(true) - $this->_startRunnerInLoopTime, 2);
    }

    public function startClubsTime()
    {
        $this->_startClubsTime = microtime(true);
    }
    public function endClubsTime()
    {
        $this->_clubsDuration += round(microtime(true) - $this->_startClubsTime, 2);
    }

    public function endSplitsTime()
    {
        $this->_splitDuration += round(microtime(true) - $this->_startsTimeSplits, 2);
    }

    public function addOneSplit()
    {
        $this->splitAmount++;
    }

    public function addOneRunnerToCounter()
    {
        $this->runnerResultsCount++;
    }

    public function toArrayError(array $human): array
    {
        return [
            'data' => null,
            'meta' => [
                'updated' => [
                    'classes' => 0,
                    'runners' => 0,
                ],
                'human' => $human
            ]
        ];
    }

    public function toArray(string $type): array
    {
        $now = new FrozenTime();
        $newLine = "\n       ";
        $runnersInLoop = $this->_runnersInLoopDuration;
        $loopingTime = $this->_runnersOutLoopDuration - $runnersInLoop;
        $resultsTotal = round($this->_runnersOutLoopDuration, 2);
        $processingDuration = round($this->_processingDuration, 2);
        $savingDuration = round($this->_savingDuration, 2);
        $total = round($this->_totalDuration, 2);
        return [
            'meta' => [
                'updated' => [
                    'classes' => $this->classCount,
                    'courses' => $this->coursesCount,
                    'runners' => $this->runnerCount,
                    'splits' => $this->splitAmount,
                    'runnerResults' => $this->runnerResultsCount,
                ],
                'timings' => [
                    'processing' => [
                        'courses' => $this->_coursesDuration,
                        'runners' => [
                            'runnerLoop' => $loopingTime,
                            'runnersInLoop' => $runnersInLoop,
                            'clubs' => $this->_clubsDuration,
                            'runnerResults' => $this->_runnerResultsDuration,
                            'splits' => $this->_splitDuration,
                            'total' => $resultsTotal,
                        ],
                        'total' => $processingDuration
                    ],
                    'saving' => [
                        'total' => $savingDuration
                    ],
                    'total' => $total,
                ],
                'humanColor' => '#FF0000',
                'human' => [
                    "\n *** Updated $this->classCount classes, "
                    . "$this->coursesCount courses ($this->_coursesDuration s) $newLine"
                    . "$this->runnerCount runners "
                    . "(and $this->runnerResultsCount results in $resultsTotal s "
                    . "[$loopingTime looping + $runnersInLoop s + $this->_clubsDuration clubs + "
                    . "$this->_runnerResultsDuration results]), $newLine"
                    . "$this->splitAmount splits (in $this->_splitDuration s), $newLine"
                    . "   in $total seconds ($processingDuration processing + $savingDuration saving) $newLine",
                    "($now - $type)\n" . 'second line for testing',
                ]
            ],
            'data' => $this->_classesToSave,
        ];
    }

    public function toArrayLegacy(string $type): array
    {
        $processingDuration = round($this->_processingDuration, 2);
        $savingDuration = round($this->_savingDuration, 2);
        $total = round($this->_totalDuration, 2);
        $now = new FrozenTime();
        $res = $this->toArray($type);
        unset($res['meta']['updated']['courses']);
        unset($res['meta']['updated']['splits']);
        unset($res['meta']['updated']['runnerResults']);
        unset($res['meta']['timings']);
        unset($res['meta']['humanColor']);
        unset($res['meta']['human'][1]);
        $res['meta']['human'] = [
            "Updated $this->runnerCount runners, "
            . "$this->classCount classes, "
            . "$this->splitAmount splits, "
            . "($now - $type) in $total seconds ($processingDuration processing + $savingDuration saving)",
        ];
        //*/
        return $res;
    }
}
