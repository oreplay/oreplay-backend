<?php

declare(strict_types = 1);

namespace Rankings\Model\Traits;

use Cake\Log\LogTrait;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
use Results\Lib\Consts\StatusCode;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;

trait ParticipantTrait
{
    use LogTrait;

    private ParticipantInterface $_leader;
    private Ranking $_settings;

    public function setSettings(Ranking $settings): ParticipantInterface
    {
        $this->_settings = $settings;
        return $this;
    }

    public static function sortResults(): \Closure
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

    public function isLeader(): bool
    {
        $stage = $this->_getStage();
        return $stage && $stage->position === 1;
    }

    public function setLeader(ParticipantInterface $runner): ParticipantInterface
    {
        $this->_leader = $runner;
        return $this;
    }

    private function _getLeader(): ParticipantInterface
    {
        return $this->_leader;
    }

    public function _getRankingPoints(): ?float
    {
        $leader = $this->_getLeader()->_getStage();
        $leaderScore = $leader->points_final;
        $leaderTime = $leader->time_seconds;
        $runnerResult = $this->_getStage();
        $ncScore = $this->_settings->_getNcScore((bool)$this->is_nc);
        if ($ncScore !== null) {
            return $ncScore;
        }
        $statusScore = $this->_settings->_getStatusScore((string)$runnerResult->status_code);
        if ($statusScore !== null) {
            return $statusScore;
        }
        $runnerScore = $runnerResult->points_final;
        $runnerTime = $runnerResult->time_seconds;
        if (!$runnerTime && !$leaderScore) {
            return null;
        }
        if ($leaderScore) {
            $runnerPoints = $runnerScore / $leaderScore;
        } else {
            $runnerPoints = $leaderTime / $runnerTime;
        }
        if ($runnerPoints > 1) {
            // throw new BadRequestException('Invalid points calculation for runner id: ' . $this->id);
            $this->log('Invalid points calculation for runner id: ' . $this->id);
        }
        $points = $runnerPoints * $this->_settings->_getTopPoints();
        return (float)round($points, $this->_settings->_getRoundPrecision());
    }
}
