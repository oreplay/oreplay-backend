<?php

declare(strict_types = 1);

namespace Rankings\Model\Traits;

use Cake\Log\LogTrait;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
use Results\Lib\ResultsFilter;
use Results\Model\Entity\Overalls;

trait ParticipantTrait
{
    use LogTrait;

    private ParticipantInterface $_leader;
    private Ranking $_settings;
    private ?Overalls $_cachedOveralls = null;

    public function setSettings(Ranking $settings): ParticipantInterface
    {
        $this->_settings = $settings;
        return $this;
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
        $calc = new SimpleScoreCalculator($this->_settings);
        return $calc->participantScore($this, $this->_getLeader());
    }

    public function isTotals(): bool
    {
        if ($this->_getStage()) {
            return false;
        }
        return !!$this->_getOveralls();
    }

    public function addPositionIfNeeded(int $newPosition): void
    {
        $position = $this->_getOveralls()['overall']['position'] ?? null;
        if ($position === ScoringAlgorithm::NEEDS_POSITION) {
            $this->_cachedOveralls->setOverallPosition($newPosition);
        }
    }

    public function _getOveralls(): ?Overalls
    {
        if ($this->_cachedOveralls === null) {
            $this->_cachedOveralls = ResultsFilter::getOveralls($this->getResultList());
        }
        return $this->_cachedOveralls;
    }
}
