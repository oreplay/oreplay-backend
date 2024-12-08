<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\I18n\FrozenTime;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnersTable;

class UploadMetrics
{
    private array $_classesToSave = [];
    private int $classCount = 0;
    private int $runnerCount = 0;
    private int $splitAmount = 0;
    private int $runnerResultsCount = 0;
    private float $_startTimeTotal = 0.0;
    private float $_startTimeProcessing = 0.0;
    private float $_processingDuration = 0.0;
    private float $_savingDuration = 0.0;
    private float $_totalDuration = 0.0;
    private float $_splitDuration = 0.0;
    private float $_runnersDuration = 0.0;
    private float $_startsTimeSplits = 0.0;

    public function __construct()
    {
        $this->startTotal();
        $this->startProcessing();
    }

    private function _roundUp(float $value, int $precision = 2): float
    {
        return round($value, $precision);
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

    public function saveManyOrFail(ClassesTable $classes, ClassEntity $singleClassToSave)
    {
        $this->_classesToSave[] = $singleClassToSave;
        $this->classCount = count($this->_classesToSave);
        $this->endProcessing();

        $startTimeSaving = microtime(true);
        $classes->saveManyOrFail([$singleClassToSave]);
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

    public function addToRunnerCounter(int $toAdd)
    {
        $this->runnerCount += $toAdd;
    }

    public function startSplitsTime()
    {
        $this->_startsTimeSplits = microtime(true);
    }

    public function endSplitsTime()
    {
        $this->_splitDuration += round(microtime(true) - $this->_startsTimeSplits, 2);
    }

    public function addOneSplit()
    {
        $this->splitAmount++;
    }

    public function addRunnerMetrics($Runners)
    {
        /** @var RunnersTable $Runners */
        $this->_runnersDuration = $Runners->RunnerResults->getRunnerTime();
        $this->runnerResultsCount = $Runners->runnerResultsCount;
        $runnersDuration1 = $Runners->RunnerResults->getRunnerTime1();
        $runnersDuration2 = $Runners->RunnerResults->getRunnerTime2();
        $runnersDuration3 = $Runners->RunnerResults->getRunnerTime3();
    }

    public function toArray(string $type): array
    {
        $now = new FrozenTime();
        $newLine = "\n       ";
        return [
            'meta' => [
                'updated' => [
                    'classes' => $this->classCount,
                    'runners' => $this->runnerCount,
                    'splits' => $this->splitAmount,
                    'runnerResults' => $this->runnerResultsCount,
                ],
                'timings' => [
                    'processing' => [
                        'splits' => $this->_splitDuration,
                        'total' => $this->_processingDuration
                    ],
                    'saving' => [
                        'total' => $this->_savingDuration
                    ],
                    'total' => $this->_totalDuration,
                ],
                'humanColor' => '#FF0000',
                'human' => [
                    "\n *** Updated $this->classCount classes, $this->runnerCount runners "
                    . "[$this->_runnersDuration secs processing runners], "
                    //. "[$runnersDuration1 + $runnersDuration2 + $runnersDuration3], "
                    . "$newLine"
                    . "$this->runnerResultsCount runner_results, $this->splitAmount splits "
                    . "[$this->_splitDuration secs processing splits], $newLine"
                    . "   in $this->_processingDuration secs processing $newLine"
                    . "   + $this->_savingDuration secs saving $newLine"
                    . "   = $this->_totalDuration total seconds. $newLine",
                    "($now - $type)\n" . 'second line for testing',
                ]
            ],
            'data' => $this->_classesToSave,
        ];
    }

    public function toArrayLegacy(string $type): array
    {
        $now = new FrozenTime();
        $res = $this->toArray($type);
        unset($res['meta']['updated']['splits']);
        unset($res['meta']['updated']['runnerResults']);
        unset($res['meta']['timings']);
        unset($res['meta']['humanColor']);
        unset($res['meta']['human'][1]);
        $res['meta']['human'] = [
            "Updated $this->runnerCount runners, "
            . "$this->classCount classes, "
            . "$this->splitAmount splits [$this->_splitDuration], "
            . "($now - $type) in $this->_processingDuration secs.",
        ];
        //*/
        return $res;
    }
}
