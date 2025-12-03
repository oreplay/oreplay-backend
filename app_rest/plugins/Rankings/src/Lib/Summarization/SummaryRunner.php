<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

use Rankings\Lib\Summarization\Settings\AgeGroupDefinitions;
use Rankings\Lib\Summarization\Settings\AgeSettings;
use Results\Model\Entity\Runner;

class SummaryRunner
{
    private string $_runnerName;
    private string $_className;
    //private Runner $_runner;
    private ?float $_points;
    private ?AgeSettings $_ageSettings = null;
    private bool $_excludedFromPoints = false;
    private bool $is_nc;
    private AgeGroupDefinitions $_ageGroupDefinitions;

    public function __construct(Runner $runner, AgeGroupDefinitions $ageGroupDefinitions, int $stageOrder)
    {
        $this->_ageGroupDefinitions = $ageGroupDefinitions;
        if ($runner->_getClass()) {
            $this->_className = $runner->_getClass()->short_name;
            $this->_ageSettings = $ageGroupDefinitions->getAgeSettings($this->_className);
        }
        $this->_runnerName = $runner->_getFullName();
        $this->is_nc = $runner->is_nc;
        //$this->_runner = $runner;
        $this->_points = $this->_getPoints($runner, $stageOrder);
    }

    public function getComputablePoints(): ?float
    {
        if ($this->_excludedFromPoints) {
            return null;
        }
        if ($this->_points === null) {
            return null;
        }
        if ($this->is_nc) {
            return null;
        }
        return $this->_ageGroupDefinitions->round($this->_points * $this->_ageSettings->getClassRatio());
    }

    public function toSummary(): string
    {
        $firstName = explode(' ', $this->getRunnerName())[0];
        return $this->getComputablePoints() . ' (' . $this->_className . ') ' . $firstName;
    }

    public function getAgeGroup(): string
    {
        return $this->_ageSettings->getAgeGroup();
    }

    public function getAmountOfRunners(): int
    {
        return $this->_ageSettings->getAmountOfRunners();
    }

    private function _getPoints(Runner $runner, int $stageOrder): ?float
    {
        $overalls = $runner->_getOveralls();
        if (!$overalls) {
            return null;
        }
        foreach ($overalls->_getParts() as $part) {
            if ($part->stage_order == $stageOrder) {
                return $part->getPoints();
            }
        }
        return null;
    }

    public function setExcludedFromPoints(): void
    {
        $this->_excludedFromPoints = true;
    }

    public function getRunnerName(): string
    {
        return $this->_runnerName;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getRunnerName(),
            'class' => $this->_className,
            'points' => $this->getComputablePoints(),
        ];
    }
}
