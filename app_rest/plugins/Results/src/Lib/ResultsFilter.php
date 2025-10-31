<?php

declare(strict_types = 1);

namespace Results\Lib;

use Rankings\Model\Table\RankingsTable;
use Results\Model\Entity\Overalls;
use Results\Model\Entity\PartialOverall;
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
        $toRet = null;
        /** @var TeamResult|RunnerResult $res */
        foreach ($results as $res) {
            if ($res->result_type_id === ResultType::STAGE) {
                if ($res->leg_number && $res->leg_number !== '0') {
                    $isBiggerLegNumberInRelay = !$toRet || $toRet->leg_number < $res->leg_number;
                    if ($isBiggerLegNumberInRelay) {
                        $toRet = $res;
                    }
                } else {
                    return $res;
                }
            }
        }
        return $toRet;
    }

    public static function getOveralls(array $results = null): ?Overalls
    {
        $overalls = new Overalls();
        $overalls->setParts(ResultsFilter::getParts($results));
        $overall = ResultsFilter::getFirstOverall($results);
        if ($overall) {
            $overalls->setOverall($overall);
        }
        if (!$overalls->hasOverall() && $overalls->hasParts()) {
            $calc = RankingsTable::load()->getCalculatorByStage($results[0]->stage_id);
            $overalls = $calc->calculateOverallScore($overalls);
        }
        if ($overalls->isTotallyEmpty()) {
            return null;
        }
        return $overalls;
    }

    public static function getFirstOverall(array $results = null): ?PartialOverall
    {
        if (!$results) {
            return null;
        }
        /** @var TeamResult|RunnerResult $res */
        foreach ($results as $res) {
            if ($res->result_type_id === ResultType::OVERALL) {
                return PartialOverall::fromResult($res);
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
                $overall = PartialOverall::fromResult($res);
                $overall->setStage($stage);
                $toRet[] = $overall;
            }
        }
        usort($toRet, function ($a, $b) {
            return $a['stage_order'] <=> $b['stage_order']; // ascending
        });
        return $toRet;
    }
}
