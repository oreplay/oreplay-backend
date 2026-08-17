<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\UploadExamples;

use Cake\I18n\FrozenTime;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Import\Iof\IofStatusMap;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Split;
use Results\Test\Fixture\StagesFixture;

class BigEventExamples
{
    public const EVENT_NAME = 'CEEBO';
    public const SOURCE_FILE = 'Splits_CEEBO.json';

    private const RADIO_CONTROLS = 'Radiocontrols';
    private const DOWNLOADED_CARDS = 'Breakdown';
    private const READING_TIME_FORMAT = 'Y-m-d\TH:i:s.v';

    private static ?array $_classes = null;

    public static function radioPunches(
        array $runnersByClass,
        int $punchAmount,
        string $stageId = StagesFixture::STAGE_FEDO_2
    ): array {
        $classes = self::_stillOnCourse(self::_selected($runnersByClass), $punchAmount);
        return self::_upload(self::RADIO_CONTROLS, $classes, $stageId);
    }

    public static function downloadedCards(
        array $runnersByClass,
        string $stageId = StagesFixture::STAGE_FEDO_2
    ): array {
        return self::_upload(self::DOWNLOADED_CARDS, self::_selected($runnersByClass), $stageId);
    }

    public static function wholeEvent(string $stageId = StagesFixture::STAGE_FEDO_2): array
    {
        return self::downloadedCards(self::everyRunner(), $stageId);
    }

    public static function everyRunner(): array
    {
        $everyRunner = [];
        foreach (self::_classes() as $class) {
            $everyRunner[$class['short_name']] = [0, null];
        }
        return $everyRunner;
    }

    private static function _upload(string $resultsType, array $classes, string $stageId): array
    {
        return [
            'configuration' => [
                'source_vendor' => 'oreplay',
                'source' => 'IofXml',
                'source_version' => '3.0',
                'contents' => 'ResultList',
                'results_type' => $resultsType,
                'utf' => true,
            ],
            'event' => [
                'id' => Event::FIRST_EVENT,
                'description' => self::EVENT_NAME,
                'stages' => [
                    [
                        'id' => $stageId,
                        'order_number' => 1,
                        'description' => self::EVENT_NAME,
                        'classes' => $classes,
                    ],
                ],
            ],
        ];
    }

    private static function _selected(array $runnersByClass): array
    {
        $selected = [];
        foreach (self::_classes() as $class) {
            $slice = $runnersByClass[$class['short_name']] ?? null;
            if (!$slice) {
                continue;
            }
            [$offset, $amount] = $slice;
            $class['runners'] = array_slice($class['runners'], $offset, $amount);
            $selected[] = $class;
        }
        return $selected;
    }

    private static function _stillOnCourse(array $classes, int $punchAmount): array
    {
        foreach ($classes as $classIndex => $class) {
            foreach ($class['runners'] as $runnerIndex => $runner) {
                $classes[$classIndex]['runners'][$runnerIndex] = self::_withoutFinish($runner, $punchAmount);
            }
        }
        return $classes;
    }

    private static function _withoutFinish(array $runner, int $punchAmount): array
    {
        $result = $runner['runner_results'][0];
        $result['splits'] = self::_asIntermediates(array_slice(self::_punchedSplits($result), 0, $punchAmount));
        $result['finish_time'] = null;
        $result['time_seconds'] = 0;
        $result['time_behind'] = 0;
        $result['position'] = 0;
        $result['status_code'] = StatusCode::OK;
        $runner['runner_results'] = [$result];
        return $runner;
    }

    private static function _punchedSplits(array $result): array
    {
        $punched = array_filter($result['splits'], fn(array $split) => $split['reading_time'] !== null);
        return array_values($punched);
    }

    private static function _asIntermediates(array $splits): array
    {
        return array_map(function (array $split) {
            $split['is_intermediate'] = true;
            return $split;
        }, $splits);
    }

    private static function _classes(): array
    {
        if (self::$_classes === null) {
            self::$_classes = array_map(
                fn(array $classResult) => self::_convertClass($classResult),
                self::_readClassResults()
            );
        }
        return self::$_classes;
    }

    private static function _readClassResults(): array
    {
        $pluginTests = dirname(__DIR__, 3);
        $decoded = json_decode(file_get_contents($pluginTests . DS . 'assets' . DS . self::SOURCE_FILE), true);
        return $decoded['ResultList']['ClassResult'];
    }

    private static function _asList(?array $singleOrMany): array
    {
        if ($singleOrMany === null) {
            return [];
        }
        return array_is_list($singleOrMany) ? $singleOrMany : [$singleOrMany];
    }

    private static function _convertClass(array $classResult): array
    {
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)$classResult['Class']['Id'],
            'short_name' => $classResult['Class']['ShortName'],
            'long_name' => $classResult['Class']['Name'],
            'course' => self::_convertCourse($classResult['Course']),
            'runners' => array_map(
                fn(array $personResult) => self::_convertRunner($personResult),
                self::_asList($classResult['PersonResult'])
            ),
        ];
    }

    private static function _convertCourse(array $course): array
    {
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)$course['Id'],
            'short_name' => $course['Name'],
            'distance' => (string)$course['Length'],
            'climb' => '',
            'controls' => (int)$course['NumberOfControls'],
        ];
    }

    private static function _convertRunner(array $personResult): array
    {
        $person = $personResult['Person'];
        $result = $personResult['Result'];
        return [
            'id' => '',
            'uuid' => '',
            'sicard' => (string)$result['ControlCard'],
            'sicard_alt' => '',
            'first_name' => $person['Name']['Given'],
            'last_name' => $person['Name']['Family'],
            'sex' => $person['sex'] ?? '',
            'bib_number' => (string)$result['BibNumber'],
            'is_nc' => false,
            'club' => self::_convertClub($personResult['Organisation']),
            'runner_results' => [self::_convertResult($result)],
        ];
    }

    private static function _convertClub(array $organisation): array
    {
        return [
            'id' => '',
            'uuid' => '',
            'oe_key' => (string)$organisation['Id'],
            'short_name' => $organisation['ShortName'],
            'long_name' => $organisation['Name'],
        ];
    }

    private static function _convertResult(array $result): array
    {
        return [
            'id' => '',
            'start_time' => $result['StartTime'],
            'finish_time' => $result['FinishTime'] ?? null,
            'time_seconds' => (int)($result['Time'] ?? 0),
            'time_behind' => (int)($result['TimeBehind'] ?? 0),
            'position' => (int)($result['Position'] ?? 0),
            'status_code' => IofStatusMap::codeOf($result['Status']),
            'leg_number' => 1,
            'splits' => self::_convertSplits($result),
            'result_type' => [
                'id' => ResultType::STAGE,
                'description' => 'Stage',
            ],
        ];
    }

    private static function _convertSplits(array $result): array
    {
        $splits = [];
        foreach (self::_asList($result['SplitTime'] ?? null) as $orderNumber => $splitTime) {
            $splits[] = [
                'sicard' => (string)$result['ControlCard'],
                'station' => (string)$splitTime['ControlCode'],
                'points' => 0,
                'reading_time' => self::_readingTime($result['StartTime'], $splitTime),
                'time_seconds' => (int)($splitTime['Time'] ?? 0),
                'order_number' => $orderNumber + 1,
                'is_intermediate' => false,
            ];
        }
        return $splits;
    }

    private static function _readingTime(string $startTime, array $splitTime): ?string
    {
        if (self::_isMissingPunch($splitTime)) {
            return null;
        }
        $readingTime = (new FrozenTime($startTime))->addSeconds((int)$splitTime['Time']);
        return $readingTime->format(self::READING_TIME_FORMAT);
    }

    private static function _isMissingPunch(array $splitTime): bool
    {
        return ($splitTime['status'] ?? '') === Split::STATUS_MISSING;
    }
}
