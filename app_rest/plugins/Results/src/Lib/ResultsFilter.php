<?php

declare(strict_types = 1);

namespace Results\Lib;

use Results\Model\Entity\ResultType;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\TeamResult;
use Results\Model\Table\StageOrdersTable;

class ResultsFilter
{
    public static function getFirstStage(array $results = null): TeamResult|RunnerResult|null
    {
        if (!$results) {
            return null;
        }
        /** @var TeamResult|RunnerResult $res */
        foreach ($results as $res) {
            if ($res->result_type_id === ResultType::STAGE) {
                return $res;
            }
        }
        return null;
    }

    public static function getOveralls(array $results = null): ?array
    {
        $parts = ResultsFilter::getParts($results);
        $teamResult = ResultsFilter::getFirstOverall($results);
        if (!$teamResult && !$parts) {
            return null;
        }
        return [
            'parts' => $parts,
            'overall' => $teamResult,
        ];
    }

    public static function getFirstOverall(array $results = null): TeamResult|RunnerResult|null
    {
        if (!$results) {
            return null;
        }
        /** @var TeamResult|RunnerResult $res */
        foreach ($results as $res) {
            if ($res->result_type_id === ResultType::OVERALL) {
                return $res;
            }
        }
        return null;
    }

    public static function getParts(array $results = null): array
    {
        $toRet = [];
        if (!$results) {
            return $toRet;
        }
        /** @var TeamResult|RunnerResult $res */
        foreach ($results as $res) {
            $typeId = $res->result_type_id;
            if (!$typeId) {
                $typeId = $res->result_type->id;
            }
            if ($typeId === ResultType::PARTIAL_OVERALL) {
                $stageOrder = (int)$res->stage_order;
                $stage = StageOrdersTable::load()->getDescriptionByOrder($stageOrder, $res->stage_id);
                $toRet[] = [
                    'id' => $res->id,
                    'stage_order' => $stageOrder,
                    'stage' => $stage,
                    'position' => $res->position,
                    'time_seconds' => $res->time_seconds,
                    'points_final' => $res->points_final,
                ];
            }
        }
        usort($toRet, function ($a, $b) {
            return $a['stage_order'] <=> $b['stage_order']; // ascending
        });
        return $toRet;
    }
}
