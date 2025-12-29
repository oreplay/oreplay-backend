<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\I18n\FrozenTime;
use RestApi\Model\Entity\RestApiEntity;
use Results\Lib\Consts\Color;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\ClassesTable;

class UploadMetrics
{
    private array $_classesToSave = [];
    private int $classCount = 0;
    private int $runnerCount = 0;
    private int $teamCount = 0;
    private int $splitCount = 0;
    private int $runnerResultsCount = 0;
    private int $teamResultsCount = 0;
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
    private string $_lastWarning = '';

    public function __construct()
    {
        $this->startTotal();
        $this->startProcessing();
    }

    private function _roundUp(float $value): float
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
        //$this->addToRunnerCounter(count($singleClassToSave->runners));
        //$this->addToTeamCounter(count($singleClassToSave->teams));
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
        $this->_totalDuration = $this->getTotalTime();
    }

    public function getTotalTime()
    {
        $end = microtime(true);
        return $this->_roundUp($end - $this->_startTimeTotal);
    }

    public function isTakingTooLong(): bool
    {
        $maxProcessingSeconds = 45;
        return $this->getTotalTime() > $maxProcessingSeconds;
    }

    public function addToRunnerCounter(int $toAdd)
    {
        $this->runnerCount += $toAdd;
    }

    public function addToTeamCounter(int $toAdd)
    {
        $this->teamCount += $toAdd;
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
        $this->splitCount++;
    }

    public function addOneRunnerResultToCounter()
    {
        $this->runnerResultsCount++;
    }

    public function addOneTeamResultToCounter()
    {
        $this->teamResultsCount++;
    }

    public function toArrayError(array $human): array
    {
        return [
            RestApiEntity::CLASS_NAME => 'Uploaded',
            'meta' => [
                RestApiEntity::CLASS_NAME => 'UploadedMeta',
                'updated' => [
                    'classes' => 0,
                    'runners' => 0,
                ],
                'humanColor' => Color::RED,
                'human' => $human
            ],
            'data' => $this->_classesToSave,
        ];
    }

    public function setWarning(string $string)
    {
        $this->_lastWarning = $string;
    }

    private function _formatExtraMessage(): string
    {
        if (!$this->_lastWarning) {
            return '';
        }
        return ' (<b>' . $this->_lastWarning . '</b>)';
    }

    public function toArray(string $type): array
    {
        $now = new FrozenTime();
        $newLine = "<br>";
        $runnersInLoop = $this->_runnersInLoopDuration;
        $loopingTime = $this->_runnersOutLoopDuration - $runnersInLoop;
        $resultsTotal = round($this->_runnersOutLoopDuration, 2);
        $processingDuration = round($this->_processingDuration, 2);
        $savingDuration = round($this->_savingDuration, 2);
        $total = round($this->_totalDuration, 2);
        $participantResultsCount = $this->runnerResultsCount + $this->teamResultsCount;
        $participantCount = $this->runnerCount + $this->teamCount;
        $humanColor = Color::GREEN;
        if (!$this->classCount) {
            $humanColor = Color::BLUE;
        }
        if ($participantCount && !$this->splitCount) {
            if (in_array($type, [UploadTypes::FINISH_TIMES, UploadTypes::INTERMEDIATES, UploadTypes::SPLITS])) {
                if (!$this->_formatExtraMessage()) {
                    if ($this->teamCount > 0) {
                        $humanColor = Color::ORANGE;
                        $this->setWarning('Uploading results without splits');
                    }
                }
            }
        }
        $extraMessage = $this->_formatExtraMessage();
        if ($extraMessage) {
            if ($humanColor !== Color::ORANGE) {
                $humanColor = Color::RED;
            }
        }
        return [
            'meta' => [
                'updated' => [
                    'classes' => $this->classCount,
                    'courses' => $this->coursesCount,
                    'runners' => $participantCount,
                    'splits' => $this->splitCount,
                    'runnerResults' => $participantResultsCount,
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
                'humanColor' => $humanColor,
                'human' => [
                    "Updated$extraMessage $this->classCount classes, "
                    . "$this->coursesCount courses ($this->_coursesDuration s) $newLine"
                    . "$participantCount participants "
                    . "(and $participantResultsCount results in $resultsTotal s "
                    . "[$loopingTime looping + $runnersInLoop s + $this->_clubsDuration clubs + "
                    . "$this->_runnerResultsDuration results]), $newLine"
                    . "$this->splitCount splits (in $this->_splitDuration s), $newLine"
                    . "in $total seconds ($processingDuration processing + $savingDuration saving)",
                    "($now - $type)",
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
        unset($res['meta']['human'][1]);
        $participantCount = $this->runnerCount + $this->teamCount;
        $extraMessage = $this->_formatExtraMessage();
        $res['meta']['human'] = [
            " *** PLEASE UPDATE THE DESKTOP CLIENT TO THE LAST VERSION!!!!!!!!!!!!!!!!!!!!! "
            . "Updated$extraMessage $participantCount participants, "
            . "$this->classCount classes, "
            . "$this->splitCount splits, "
            . "($now - $type) in $total seconds ($processingDuration processing + $savingDuration saving)",
        ];
        //*/
        return $res;
    }
}
