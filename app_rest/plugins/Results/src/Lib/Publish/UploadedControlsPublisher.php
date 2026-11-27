<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Split;
use Results\Model\Entity\Team;

class UploadedControlsPublisher implements UploadPublisher
{
    public function __construct(
        private readonly ControlChangedPublisher $controls,
        private readonly ?string $uploadId = null
    ) {
    }

    public function classImported(string $stageId, ClassImportReport $report): void
    {
        $class = $report->getClass();
        if (!$class) {
            return;
        }
        $stations = [];
        foreach ($class->runners ?? [] as $runner) {
            $stations = array_merge($stations, $this->_stationsOfRunner($runner));
        }
        foreach ($class->teams ?? [] as $team) {
            $stations = array_merge($stations, $this->_stationsOfTeam($team));
        }
        $this->controls->publish($stageId, $stations, ControlChangedPublisher::SOURCE_UPLOAD, $this->uploadId);
    }

    /**
     * @return string[]
     */
    private function _stationsOfTeam(Team $team): array
    {
        $stations = $this->_stationsOfResults($team->getResultList() ?? []);
        foreach ($team->getRunnerList() ?? [] as $runner) {
            $stations = array_merge($stations, $this->_stationsOfRunner($runner));
        }
        return $stations;
    }

    /**
     * @return string[]
     */
    private function _stationsOfRunner(Runner $runner): array
    {
        return $this->_stationsOfResults($runner->getResultList() ?? []);
    }

    /**
     * @return string[]
     */
    private function _stationsOfResults(array $results): array
    {
        $stations = [];
        foreach ($results as $result) {
            foreach ($result->getSplits() as $split) {
                $stations[] = $this->_stationOf($split);
            }
        }
        return $stations;
    }

    private function _stationOf(Split $split): string
    {
        return (string)$split->station;
    }
}
