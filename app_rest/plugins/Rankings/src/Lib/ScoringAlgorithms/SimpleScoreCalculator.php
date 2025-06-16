<?php

declare(strict_types = 1);

namespace Rankings\Lib\ScoringAlgorithms;

use Cake\Log\LogTrait;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
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

    public function calculateOverallScore(array $parts): PartialOverall
    {
        if (!$parts) {
            return PartialOverall::fromValues();
        }
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
        return PartialOverall::fromValues(count($parts), ScoringAlgorithm::NEEDS_POSITION, $sumSeconds, $sumPoints);
    }
}
