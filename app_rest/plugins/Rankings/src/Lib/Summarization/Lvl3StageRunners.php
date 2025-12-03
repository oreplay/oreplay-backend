<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

class Lvl3StageRunners
{
    /** @var Lvl4AgeGroupRunners[] */
    private array $_ageGroupMap = [];

    public function addRunnerToStage(SummaryRunner $runner): void
    {
        $ageGroup = $runner->getAgeGroup();
        if (!isset($this->_ageGroupMap[$ageGroup])) {
            $this->_ageGroupMap[$ageGroup] = new Lvl4AgeGroupRunners();
        }
        $this->_ageGroupMap[$ageGroup]->addRunnerToAgeGroup($runner);
    }

    public function getPointsInStage(): float
    {
        $points = 0;
        foreach ($this->_ageGroupMap as $group) {
            $points += $group->getPointsInAgeGroup();
        }
        return $points;
    }

    public function toArray(): array
    {
        $ageGroups = [];
        foreach ($this->_ageGroupMap as $key => $group) {
            $ageGroups[$key] = $group->toArray();
        }
        return [
            'points' => $this->getPointsInStage(),
            'age_groups' => $ageGroups,
        ];
    }

    public function getStageRunnerSummary(string $string): string
    {
        foreach ($this->_ageGroupMap as $key => $group) {
            if ($string === $key) {
                return $group->toSummaryRunnersPerGroup();
            }
        }
        return '';
    }
}
