<?php

declare(strict_types = 1);

namespace Results\Lib;

use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;

class ResultsSorter
{
    public static function sortStages(): \Closure
    {
        return function (Team|Runner $a, Team|Runner $b): int {
            $resA = $a->_getStage();
            $resB = $b->_getStage();
            $aOk = $resA->status_code === StatusCode::OK && !$a->is_nc;
            $bOk = $resB->status_code === StatusCode::OK && !$b->is_nc;
            if ($aOk && $bOk) {
                return $resA->position <=> $resB->position;
            }
            if (!$aOk && !$bOk) {
                return strcmp($a->_getFullName(), $b->_getFullName());
            }
            return $bOk <=> $aOk;
        };
    }

    public static function sortTotals(): \Closure
    {
        return function (Team|Runner $a, Team|Runner $b): int {
            $posA = $a->_getOveralls()->_getOverall()['position'] ?? null;
            $pointsA = $a->_getOveralls()->_getOverall()['points_final'] ?? null;
            $timeA = $a->_getOveralls()->_getOverall()['time_seconds'] ?? null;

            $posB = $b->_getOveralls()->_getOverall()['position'] ?? null;
            $pointsB = $b->_getOveralls()->_getOverall()['points_final'] ?? null;
            $timeB = $b->_getOveralls()->_getOverall()['time_seconds'] ?? null;

            if ($posA !== $posB) {
                return $posA <=> $posB;
            }
            if ($pointsA !== $pointsB) {
                return $pointsB <=> $pointsA;
            }
            return $timeA <=> $timeB;
        };
    }
}
