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
        if ($leaderScore && $leaderScore != '0') {
            $runnerPoints = $this->_divide($runnerScore, $leaderScore);
        } else {
            $runnerPoints = $this->_divide($leaderTime, $runnerTime);
        }
        if ($runnerPoints > 1) {
            $id = ($participant['id'] ?? '');
            // throw new BadRequestException('Invalid points calculation for runner id: ' . $id);
            $this->log('Invalid points calculation for runner id: ' . $id);
        }
        $points = $runnerPoints * $this->_settings->_getMaxPoints();
        return $this->_round($points);
    }

    private function _divide($x, $y): float
    {
        if (!$y || $y == '0') {
            return 0.0;
        }
        return (float)$x / $y;
    }

    private function _round(float|int $number): float
    {
        if ($this->_settings->isFloorInsteadOfRound()) {
            return floor($number);
        }
        return round($number, $this->_settings->_getRoundPrecision());
    }

    private function _getOrgComputableConstant(): float
    {
        // how many races will be considered in the org avg
        // e.g. use 0.3 to use 30% of the races (where the organizer was taking place) in the
        // circuit to calculate organizer average (participated in 4, used for org avg 4*0.3=1.2=2)
        return $this->_settings->_getOverallSettings()['organizerScoringFraction'];
    }

    private function _getTotalRaces(): int
    {
        // number of races in this circuit
        // e.g. use 9 if there are 9 races in the circuit
        return $this->_settings->_getOverallSettings()['totalCircuitRaces'];
    }

    private function _getMaxRacesCounted(): int
    {
        // max number of races counted for each participant
        // e.g. use 6 if only 6 out of 9 races should be used to compute the total result
        return $this->_settings->_getOverallSettings()['maxRacesCounted'];
    }

    private function _getMinAsOrg(): int
    {
        // min points got as organizer
        // e.g. if the participant should get 19 points he is getting 50 instead
        return $this->_settings->_getOverallSettings()['minPointsAsOrg'] ?? 0;
    }

    public function hasFewComputable(int $amountRacesParticipated, int $totalRaces = null): bool
    {
        if ($totalRaces === null) {
            $totalRaces = $this->_getTotalRaces();
        }
        return $amountRacesParticipated <= $totalRaces * $this->_getOrgComputableConstant();
    }

    public function getOrgComputable(int $amountRacesParticipated, int $totalRaces = null): int
    {
        if ($totalRaces === null) {
            $totalRaces = $this->_getTotalRaces();
        }
        if ($this->hasFewComputable($amountRacesParticipated, $totalRaces)) {
            return $amountRacesParticipated;
        }
        $amountToReturn = $totalRaces * $this->_getOrgComputableConstant();
        return (int)round($amountToReturn);
    }

    public function calculateOverallScore(Overalls $overalls): Overalls
    {
        $parts = $overalls->_getParts();
        if (!$parts) {
            return $overalls->setOverall(PartialOverall::fromValues());
        }
        $partsNormal = [];
        $partsOrg = [];
        foreach ($parts as &$part) {
            if ($part->isComputableOrganizer()) {
                $partsOrg[] = $part;
            } else if ($part->hasMoreThanZero()) {
                $partsNormal[] = $part;
            }
        }
        usort($partsNormal, PartialOverall::sortTotals());
        $orgComputable = array_slice($partsNormal, 0, $this->getOrgComputable(count($partsNormal)));

        list($sumSeconds, $sumPoints) = $this->sum($orgComputable);
        $avgSeconds = 0;
        $avgPoints = 0;
        $amountNormal = count($orgComputable);
        if ($amountNormal) {
            $avgSeconds = $this->_divide($sumSeconds, $amountNormal);
            $avgPoints = $this->_divide($sumPoints, $amountNormal);
            if ($avgPoints < $this->_getMinAsOrg()) {
                $avgPoints = $this->_getMinAsOrg();
            }
        }
        $overalls->updateComputedOrganizers($this->_round($avgPoints), (int)$avgSeconds);

        $partsToSum = $overalls->getBestParts($this->_getMaxRacesCounted());

        list($sumSeconds, $sumPoints) = $this->sum($partsToSum);

        $amountOrg = count($partsOrg);
        if ($sumSeconds) {
            $sumSeconds = $this->_round($sumSeconds);
        }
        if ($sumPoints) {
            $sumPoints = $this->_round($sumPoints);
        }
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
