<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\I18n\FrozenTime;
use Results\Model\Entity\Runner;

class TeamRunnerHelper
{
    private ?FrozenTime $firstStart = null;
    private ?FrozenTime $lastFinish = null;

    public function getFirstStart(): ?FrozenTime
    {
        return $this->firstStart;
    }

    public function getLastFinish(): ?FrozenTime
    {
        return $this->lastFinish;
    }

    /**
     * @param Runner[]|null $runnerList
     * @return void
     */
    public function setFromRunners(?array $runnerList): void
    {
        if (!$runnerList) {
            return;
        }
        $amountOfFinishTimesNotNull = 0;
        foreach ($runnerList as $runner) {
            $stage = $runner->_getStage();
            if ($stage) {
                if ($stage->start_time) {
                    if (!$this->firstStart || $stage->start_time < $this->firstStart) {
                        $this->firstStart = $stage->start_time;
                    }
                }
                if ($stage->finish_time) {
                    $amountOfFinishTimesNotNull++;
                    if (!$this->lastFinish || $stage->finish_time > $this->lastFinish) {
                        $this->lastFinish = $stage->finish_time;
                    }
                }
            }
        }
        if ($amountOfFinishTimesNotNull !== count($runnerList)) {
            $this->lastFinish = null;
        }
    }
}
