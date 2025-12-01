<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

class AgeGroupRunners
{
    /** @var SummaryRunner[] */
    private array $_runners;

    public function addRunnerToAgeGroup(SummaryRunner $runner): void
    {
        $this->_runners[] = $runner;
    }

    public function getPointsInAgeGroup(): float
    {
        if ($this->_runners) {
            usort($this->_runners, function (SummaryRunner $a, SummaryRunner $b): int {
                return $b->getComputablePoints() <=> $a->getComputablePoints();
            });
        }
        $points = 0;
        $amountOfRunnersSummed = 1;
        foreach ($this->_runners as $runner) {
            if ($amountOfRunnersSummed <= $runner->getAmountOfRunners()) {
                $points += $runner->getComputablePoints();
                $amountOfRunnersSummed++;
            } else {
                $runner->setExcludedFromPoints();
            }
        }
        return $points;
    }

    public function toSummaryRunnersPerGroup(): string
    {
        $runnersArray = [];
        foreach ($this->_runners as $runner) {
            $computablePoints = $runner->getComputablePoints();
            if ($computablePoints) {
                $runnersArray[] = $runner->toSummary();
            }
        }
        // if $runnersArray has less than 5 elements, we add empty elements
        while (count($runnersArray) < 5) {
            $runnersArray[] = '';
        }
        $runnersArray[] = '-------------------------';
        $runnersArray[] = $this->getPointsInAgeGroup();
        return '"' . implode("\n", $runnersArray) . '"';
    }

    public function toArray()
    {
        $runnersArray = [];
        foreach ($this->_runners as $runner) {
            $computablePoints = $runner->getComputablePoints();
            if ($computablePoints) {
                $runnersArray[] = $runner->toArray();
            }
        }
        return [
            '_c' => 'AgeGroup',
            'runners' => $runnersArray,
            'total_points' => $this->getPointsInAgeGroup(),
        ];
    }
}
