<?php

declare(strict_types = 1);

namespace Rankings\Lib\ScoringAlgorithms;

use Cake\Log\LogTrait;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Overalls;
use Results\Model\Entity\PartialOverall;

class SimpleScoreCalculator implements ScoringAlgorithm
{
    use LogTrait;

    private Ranking $_settings;

    public function __construct(Ranking $settings)
    {
        $this->_settings = $settings;
    }

    public function participantScore(ParticipantInterface $participant, ?ParticipantInterface $leader): ?float
    {
        $leader = $leader->_getStage();
        $leaderScore = $leader->points_final;
        $leaderTime = $leader->time_seconds;
        $runnerResult = $participant->_getStage();
        $ncScore = $this->_settings->_getNcScore((bool)$participant->is_nc);
        if ($ncScore !== null) {
            return $ncScore;
        }
        $statusScore = $this->_settings->getStatusScore((string)$runnerResult->status_code);
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
            $id = ($participant['id'] ?? '');
            // throw new BadRequestException('Invalid points calculation for runner id: ' . $id);
            $this->log('Invalid points calculation for runner id: ' . $id);
        }
        $points = $runnerPoints * $this->_settings->_getMaxPoints();
        return (float)round($points, $this->_settings->_getRoundPrecision());
    }

    public function calculateOverallScore(Overalls $overalls): Overalls
    {
        $parts = $overalls->_getParts();
        if (!$parts) {
            return $overalls->setOverall(PartialOverall::fromValues());
        }
        $partsNormal = [];
        $partsOrg = [];
        foreach ($parts as $part) {
            if ($part->isComputableOrganizer()) {
                $partsOrg[] = $part;
            } else {
                $partsNormal[] = $part;
            }
        }
        list($sumSeconds, $sumPoints) = $this->sum($partsNormal);
        $amountNormal = count($partsNormal);
        if ($amountNormal) {
            $avgSeconds = $sumSeconds / $amountNormal;
            $avgPoints = $sumPoints / $amountNormal;
        }
        $amountOrg = count($partsOrg);
        foreach ($overalls->_getParts() as $part) {
            if ($part->isComputableOrganizer()) {
                $part->setPoints($avgPoints);
                $part->setTimeSeconds($avgSeconds);
            }
        }
        $sumSeconds = round($sumSeconds + $avgSeconds * $amountOrg, $this->_settings->_getRoundPrecision());
        $sumPoints = round($sumPoints + $avgPoints * $amountOrg, $this->_settings->_getRoundPrecision());
        $res = PartialOverall::fromValues(
            $amountNormal + $amountOrg,
            ScoringAlgorithm::NEEDS_POSITION,
            $sumSeconds,
            $sumPoints,
            UploadTypes::RANKING_COMPUTED
        );
        return $overalls->setOverall($res);
    }

    private function sum(array $parts): array
    {
        $sumSeconds = null;
        $sumPoints = null;
        /** @var PartialOverall $part */
        foreach ($parts as $part) {
            $points = $part->getPoints();
            if ($points !== null) {
                $sumPoints += $points;
            }
            $seconds = $part['time_seconds'] ?? null;
            if ($seconds !== null) {
                $sumSeconds += $seconds;
            }
        }
        return array($sumSeconds, $sumPoints);
    }
}
