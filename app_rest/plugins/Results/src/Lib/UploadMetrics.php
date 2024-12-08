<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\I18n\FrozenTime;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;

class UploadMetrics
{
    private array $_classesToSave;
    private int $classCount = 0;
    private int $runnerCount = 0;
    private int $splitAmount = 0;
    private int $runnerResultsCount = 0;
    private float $_startTimeProcessing = 0.0;
    private float $_processingDuration = 0.0;
    private float $_savingDuration = 0.0;
    private float $_totalDuration = 0.0;
    private float $_splitDuration = 0.0;
    private float $_runnersDuration = 0.0;

    private function _roundUp(float $value, int $precision = 2): float
    {
        return round($value, $precision);
    }

    public function startProcessing()
    {
        $this->_startTimeProcessing = microtime(true);
    }

    private function endProcessing()
    {
        $this->_processingDuration = $this->_roundUp(microtime(true) - $this->_startTimeProcessing);
    }

    public function saveManyOrFail(ClassesTable $classes, array $classesToSave)
    {
        $this->_classesToSave = $classesToSave;
        $this->classCount = count($classesToSave);
        $this->endProcessing();

        $startTimeSaving = microtime(true);
        $classes->saveManyOrFail($classesToSave);
        $end = microtime(true);
        $this->_savingDuration = $this->_roundUp($end - $startTimeSaving);
        $this->_totalDuration = $this->_roundUp($end - $this->_startTimeProcessing);
    }

    public function addToRunnerCounter(int $toAdd)
    {
        $this->runnerCount += $toAdd;
    }

    public function addSplitsMetrics($splits)
    {
        /** @var SplitsTable $splits */
        $this->splitAmount = $splits->getSplitAmount();
        $this->_splitDuration = $this->_roundUp($splits->getSplitTime());
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
                    "\n *** Updated $this->runnerCount runners "
                    . "[$this->_runnersDuration secs processing runners], "
                    //. "[$runnersDuration1 + $runnersDuration2 + $runnersDuration3], "
                    . "$this->classCount classes, $newLine"
                    . "$this->runnerResultsCount runner_results, $this->splitAmount splits "
                    . "[$this->_splitDuration secs processing splits], $newLine"
                    . "($now - $type) $newLine   in $this->_totalDuration secs "
                    . "(processing $this->_processingDuration + saving $this->_savingDuration).\n",
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
        $res['meta']['human'] = [
            "Updated $this->runnerCount runners, "
            . "$this->classCount classes, "
            . "$this->splitAmount splits [$this->_splitDuration], "
            . "($now - $type) in $this->_processingDuration secs.",
        ];
        return $res;
    }
}
