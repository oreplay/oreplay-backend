<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Http\Exception\InternalErrorException;
use Cake\I18n\FrozenTime;
use RestApi\Model\Entity\RestApiEntity;
use Results\Lib\Consts\Color;
use Results\Lib\Consts\MessageLevel;
use Results\Lib\Consts\UploadTypes;
use Results\Lib\Import\RowsToInsert;
use Results\Lib\Import\SavedRowsCheck;
use Results\Lib\Import\SplitsToReplace;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Course;
use Results\Model\Table\ClassesTable;

class UploadMetrics
{
    public const COURSES = 'courses';
    // covers participant matching as well as club creation, both importers measure
    // createRunnerIfNotExists() and createTeamIfNotExists() with it
    public const CLUBS = 'clubs';
    public const PARTICIPANT_RESULTS = 'participantResults';
    public const SPLITS = 'splits';
    public const PARTICIPANTS_LOOP = 'participantsLoop';
    public const PARTICIPANTS_IN_LOOP = 'participantsInLoop';

    private array $_classesToSave = [];
    private bool $_keepSavedClasses = true;
    private int $classCount = 0;
    private int $runnerCount = 0;
    private int $teamCount = 0;
    private int $splitCount = 0;
    private int $runnerResultsCount = 0;
    private int $teamResultsCount = 0;
    private array $_courseIdsTouched = [];
    private float $_startTimeTotal = 0.0;
    private float $_startTimeProcessing = 0.0;
    private float $_processingDuration = 0.0;
    private float $_savingDuration = 0.0;
    private float $_totalDuration = 0.0;
    private array $_durations = [
        self::COURSES => 0.0,
        self::CLUBS => 0.0,
        self::PARTICIPANT_RESULTS => 0.0,
        self::SPLITS => 0.0,
        self::PARTICIPANTS_LOOP => 0.0,
        self::PARTICIPANTS_IN_LOOP => 0.0,
    ];
    private array $_timersInProgress = [];
    private const MAX_WARNINGS_PER_TYPE = 10;
    public const CODE_UNCLASSIFIED = 'unclassified';
    public const CODE_ROWS_NOT_SAVED = 'rows_not_saved';
    public const CODE_DUPLICATED_RUNNER = 'duplicated_runner';
    public const CODE_TAKING_TOO_LONG = 'taking_too_long';
    public const CODE_RUNNER_WITHOUT_RESULTS = 'runner_without_results';
    public const CODE_TEAM_WITHOUT_RESULTS = 'team_without_results';
    public const CODE_TEAM_WITHOUT_RUNNERS = 'team_without_runners';
    public const CODE_RESULT_TYPE_CONVERTED = 'result_type_converted';
    public const CODE_FINISH_WITHOUT_SECONDS = 'finish_time_without_seconds';
    public const CODE_EVENT_WITHOUT_TIME_ZONE = 'event_without_time_zone';
    public const CODE_UPLOAD_TYPE_GUESSED = 'upload_type_guessed';
    public const CODE_NOTHING_CHANGED = 'nothing_changed';
    public const CODE_RESULTS_WITHOUT_SPLITS = 'results_without_splits';

    private array $_warnings = [];
    private array $_dataLossWarnings = [];

    public function __construct()
    {
        $this->startTotal();
        $this->startProcessing();
    }

    public static function withoutSavedClasses(): self
    {
        $metrics = new self();
        $metrics->_keepSavedClasses = false;
        return $metrics;
    }

    public function measure(string $timer, callable $work)
    {
        if (!array_key_exists($timer, $this->_durations)) {
            throw new InternalErrorException('Unknown upload timer ' . $timer);
        }
        if (isset($this->_timersInProgress[$timer])) {
            // an outer measurement of this timer already covers this span
            return $work();
        }
        $this->_timersInProgress[$timer] = true;
        $start = microtime(true);
        try {
            return $work();
        } finally {
            unset($this->_timersInProgress[$timer]);
            $this->_durations[$timer] += microtime(true) - $start;
        }
    }

    private function _duration(string $timer): float
    {
        return round($this->_durations[$timer], 2);
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
        $this->_processingDuration += microtime(true) - $this->_startTimeProcessing;
    }

    public function saveManyOrFail(
        ClassesTable $classes,
        ClassEntity $singleClassToSave,
        SplitsToReplace $splitsToReplace,
        RowsToInsert $rowsToInsert
    ): void {
        $this->classCount++;
        if ($this->_keepSavedClasses) {
            $this->_classesToSave[] = $singleClassToSave;
        }
        $this->endProcessing();

        $startTimeSaving = microtime(true);
        $missing = (new SavedRowsCheck())->rowsMissingAfter(
            $singleClassToSave,
            fn() => $classes->saveManyWithRelations($singleClassToSave, $splitsToReplace, $rowsToInsert)
        );
        if ($missing) {
            $this->setDataLossWarning(
                'Not saved in database: ' . $missing . ' rows of class ' . $singleClassToSave->short_name,
                self::CODE_ROWS_NOT_SAVED,
                ['class' => $singleClassToSave->short_name, 'rows' => $missing]
            );
        }
        $end = microtime(true);
        $this->_savingDuration += $end - $startTimeSaving;
        $this->startProcessing();
    }

    public function endTotalTimer()
    {
        $this->_totalDuration = $this->getTotalTime();
    }

    public function getTotalTime()
    {
        $end = microtime(true);
        return $end - $this->_startTimeTotal;
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

    public function addCourse(Course $course)
    {
        $this->_courseIdsTouched[$course->id] = true;
    }

    private function _courseCount(): int
    {
        return count($this->_courseIdsTouched);
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

    public function setWarning(string $string, string $code = self::CODE_UNCLASSIFIED, array $context = [])
    {
        $this->_warnings = $this->_appendCapped(
            $this->_warnings,
            UploadMessage::warning($code, $string, $context)
        );
    }

    /**
     * For warnings that mean rows or people were lost, which outrank everything else on the way out.
     */
    public function setDataLossWarning(string $string, string $code = self::CODE_UNCLASSIFIED, array $context = [])
    {
        $this->_dataLossWarnings = $this->_appendCapped(
            $this->_dataLossWarnings,
            UploadMessage::error($code, $string, $context)
        );
    }

    // keeps the most recent so a flood of warnings cannot exhaust memory, and repeats are dropped:
    // the too-long warning is re-set once per remaining class and would otherwise fill the list
    private function _appendCapped(array $warnings, UploadMessage $warning): array
    {
        $last = end($warnings);
        if ($last && $last->getText() === $warning->getText()) {
            return $warnings;
        }
        $warnings[] = $warning;
        return array_slice($warnings, -self::MAX_WARNINGS_PER_TYPE);
    }

    // losing data outranks the rest: report every such warning and drop the ordinary ones, so the
    // lines that matter are not buried. With no data loss only the last ordinary warning is useful.
    private function _warningsToShow(): array
    {
        $shown = $this->_dataLossWarnings ?: array_slice($this->_warnings, -1);
        return array_map(fn(UploadMessage $message) => $message->getText(), $shown);
    }

    private function _formatExtraMessage(): string
    {
        $shown = $this->_warningsToShow();
        if (!$shown) {
            return '';
        }
        return ' (<b>' . implode('; ', $shown) . '</b>)';
    }

    private function _warnIfResultsWithoutSplits(string $type): bool
    {
        $withSplits = [UploadTypes::FINISH_TIMES, UploadTypes::INTERMEDIATES, UploadTypes::SPLITS];
        if (!($this->runnerCount + $this->teamCount) || $this->splitCount) {
            return false;
        }
        if (!in_array($type, $withSplits) || $this->_formatExtraMessage() || $this->teamCount <= 0) {
            return false;
        }
        $this->setWarning('Uploading results without splits', self::CODE_RESULTS_WITHOUT_SPLITS);
        return true;
    }

    /**
     * v2 only. One envelope for every answer: the HTTP status says whether the request worked, meta.level
     * says how good the outcome was, and every message survives instead of the worst kind hiding the rest.
     * See docs/uploads-v1-vs-v2.md.
     */
    public function toRestArray(string $type): array
    {
        $this->_warnIfResultsWithoutSplits($type);
        if (!$this->classCount) {
            $this->setWarning('No class needed importing, every upload hash already matched',
                self::CODE_NOTHING_CHANGED);
        }
        return [
            'meta' => [
                'level' => $this->_level(),
                'uploadType' => $type,
                'updated' => $this->_updated(),
                'timings' => $this->_timings(),
                'messages' => $this->_messagesToArray(),
            ],
            'data' => $this->_classesToSave,
        ];
    }

    public function toRestArrayError(UploadMessage $message): array
    {
        return [
            'meta' => [
                'level' => MessageLevel::ERROR,
                'uploadType' => null,
                'updated' => $this->_updated(),
                'timings' => $this->_timings(),
                'messages' => array_merge($this->_messagesToArray(), [$message->toArray()]),
            ],
            'data' => [],
        ];
    }

    private function _level(): string
    {
        foreach ($this->_allMessages() as $message) {
            if ($message->getLevel() === MessageLevel::ERROR) {
                return MessageLevel::ERROR;
            }
        }
        return $this->_warnings ? MessageLevel::WARNING : MessageLevel::INFO;
    }

    /**
     * @return UploadMessage[]
     */
    private function _allMessages(): array
    {
        return array_merge($this->_dataLossWarnings, $this->_warnings);
    }

    private function _messagesToArray(): array
    {
        return array_map(fn(UploadMessage $message) => $message->toArray(), $this->_allMessages());
    }

    private function _updated(): array
    {
        return [
            'classes' => $this->classCount,
            'courses' => $this->_courseCount(),
            'runners' => $this->runnerCount + $this->teamCount,
            'splits' => $this->splitCount,
            'runnerResults' => $this->runnerResultsCount + $this->teamResultsCount,
        ];
    }

    private function _timings(): array
    {
        $runnersInLoop = $this->_duration(self::PARTICIPANTS_IN_LOOP);
        return [
            'processing' => [
                'courses' => $this->_duration(self::COURSES),
                'runners' => [
                    'runnerLoop' => $this->_duration(self::PARTICIPANTS_LOOP) - $runnersInLoop,
                    'runnersInLoop' => $runnersInLoop,
                    'clubs' => $this->_duration(self::CLUBS),
                    'runnerResults' => $this->_duration(self::PARTICIPANT_RESULTS),
                    'splits' => $this->_duration(self::SPLITS),
                    'total' => round($this->_duration(self::PARTICIPANTS_LOOP), 2),
                ],
                'total' => round($this->_processingDuration, 2),
            ],
            'saving' => ['total' => round($this->_savingDuration, 2)],
            'total' => round($this->_totalDuration, 2),
        ];
    }

    public function toArray(string $type): array
    {
        $now = new FrozenTime();
        $newLine = "<br>";
        $runnersInLoop = $this->_duration(self::PARTICIPANTS_IN_LOOP);
        $loopingTime = $this->_duration(self::PARTICIPANTS_LOOP) - $runnersInLoop;
        $resultsTotal = round($this->_duration(self::PARTICIPANTS_LOOP), 2);
        $coursesDuration = $this->_duration(self::COURSES);
        $clubsDuration = $this->_duration(self::CLUBS);
        $participantResultsDuration = $this->_duration(self::PARTICIPANT_RESULTS);
        $splitsDuration = $this->_duration(self::SPLITS);
        $processingDuration = round($this->_processingDuration, 2);
        $savingDuration = round($this->_savingDuration, 2);
        $total = round($this->_totalDuration, 2);
        $participantResultsCount = $this->runnerResultsCount + $this->teamResultsCount;
        $participantCount = $this->runnerCount + $this->teamCount;
        $courseCount = $this->_courseCount();
        $humanColor = Color::GREEN;
        if (!$this->classCount) {
            $humanColor = Color::BLUE;
        }
        if ($this->_warnIfResultsWithoutSplits($type)) {
            $humanColor = Color::ORANGE;
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
                    'courses' => $courseCount,
                    'runners' => $participantCount,
                    'splits' => $this->splitCount,
                    'runnerResults' => $participantResultsCount,
                ],
                'timings' => [
                    'processing' => [
                        'courses' => $coursesDuration,
                        'runners' => [
                            'runnerLoop' => $loopingTime,
                            'runnersInLoop' => $runnersInLoop,
                            'clubs' => $clubsDuration,
                            'runnerResults' => $participantResultsDuration,
                            'splits' => $splitsDuration,
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
                    . "$courseCount courses ($coursesDuration s) $newLine"
                    . "$participantCount participants "
                    . "(and $participantResultsCount results in $resultsTotal s "
                    . "[$loopingTime looping + $runnersInLoop s + $clubsDuration clubs + "
                    . "$participantResultsDuration results]), $newLine"
                    . "$this->splitCount splits (in $splitsDuration s), $newLine"
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
