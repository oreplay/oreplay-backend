<?php

declare(strict_types = 1);

namespace Rankings\Model\Traits;

use Cake\Log\LogTrait;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
use Results\Lib\Consts\StatusCode;
use Results\Lib\ResultsFilter;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Overalls;

trait ParticipantTrait
{
    use LogTrait;
    public const string C_NAME = 'Participant';

    private ParticipantInterface $_leader;
    private Ranking $_settings;
    private ?Overalls $_cachedOveralls = null;

    protected function _get_c(): string
    {
        // use same class for team and runner in orval and typescript
        return self::C_NAME;
    }

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

    public function isStatusOk(): bool
    {
        $stage = $this->_getStage();
        return $stage && $stage->status_code == StatusCode::OK;
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

    public function isSameField(string $field, array $runnerData): bool
    {
        $dbId = $runnerData[$field] ?? null;
        return isset($this[$field]) && $this[$field] && $this[$field] == $dbId;
    }

    public function isSameClass(ClassEntity $class = null): null|static
    {
        if ($class) {
            if ($this->class_id == $class->id) {
                return $this;
            } else {
                return null;
            }
        } else {
            return $this;
        }
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
