<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\ResultType;

/**
 * One IOF ClassStart as the classes[] array the importers already consume. A start list carries the entry
 * and its start time and nothing else — no Status, no SplitTime, no Position — so the result is fixed
 * apart from the time.
 *
 * The shape deliberately matches what the JSON path receives for a start list (result_type Stage,
 * leg_number 1, status OK): the same stage must not look different depending on the format it arrived in,
 * because that is what the upload hash compares.
 */
class StartListMapper extends IofClassMapper
{
    protected function personElement(): string
    {
        return 'PersonStart';
    }

    protected function resultElement(): string
    {
        return 'Start';
    }

    protected function resultOf(array $result, array $runner): array
    {
        $mapped = [
            'id' => '',
            'stage_order' => self::stageOrderOf($result),
            'status_code' => StatusCode::OK,
            'leg_number' => (int)($result['Leg'] ?? 1),
            'result_type' => ['id' => ResultType::STAGE, 'description' => 'Stage'],
            'splits' => [],
        ];
        $startTime = $this->dateOf($result['StartTime'] ?? null);
        if ($startTime) {
            $mapped['start_time'] = $startTime->format(self::DATE_FORMAT);
        }
        return $mapped;
    }
}
