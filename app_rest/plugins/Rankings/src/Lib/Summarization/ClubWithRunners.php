<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

use Results\Model\Entity\Club;
use Results\Model\Entity\Runner;

class ClubWithRunners
{
    /** @var StageRunners[] */
    private array $_stages = [];
    private Club $_club;

    private AgeGroupDefinitions $_ageGroupDefinitions;

    public function __construct(AgeGroupDefinitions $ageGroupDefinitions, Club $club)
    {
        $this->_ageGroupDefinitions = $ageGroupDefinitions;
        $this->_club = $club;
    }

    public function addRunnerToClub(Runner $runner): void
    {
        $amountOfStages = $this->_ageGroupDefinitions->getAmountOfStages();
        for ($i = 0; $i < $amountOfStages; $i++) {
            $stage_order = $i + 1;
            if (!isset($this->_stages[$stage_order])) {
                $this->_stages[$stage_order] = new StageRunners();
            }
            $r = new SummaryRunner($runner, $this->_ageGroupDefinitions, $stage_order);
            if ($r->getComputablePoints() !== null) {
                $this->_stages[$stage_order]->addRunnerToStage($r);
            }
        }
    }

    public function getClubName(): string
    {
        return $this->_club->short_name;
    }

    private function _getArrayOfStrings(int $length): array
    {
        $arr = [];
        for ($i = 0; $i < $length; $i++) {
            $arr[] = '';
        }
        return $arr;
    }

    public function toCsvRows(string $separator = ';'): array
    {
        $rows = $this->getClubRows();
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = implode($separator, $row);
        }
        return $lines;
    }

    public function getCsvHeader(string $separator = ';')
    {
        $amountOfStages = $this->_ageGroupDefinitions->getAmountOfStages();
        $columns = ['Club', 'Points'];
        for ($i = 1; $i <= $amountOfStages; $i++) {
            $columns[] = '# ' . $i;
        }
        return implode($separator, $columns);
    }

    public function getClubRows(): array
    {
        $amountOfStages = $this->_ageGroupDefinitions->getAmountOfStages();
        $rows = [
            'club' => array_merge([$this->getClubName(), $this->getPointsPerClub()], $this->getStagePoints()),
        ];
        foreach ($this->_stages as $stageNumber => $stage) {
            foreach ($this->_ageGroupDefinitions->getAllAgeGroups() as $keyType) {
                if (!isset($rows[$keyType])) {
                    $rows[$keyType] = $this->_getArrayOfStrings(2 + $amountOfStages);
                }
                $rows[$keyType][1] = $this->_ageGroupDefinitions->groupTranslation($keyType);
                $rows[$keyType][$stageNumber + 1] = $stage->getStageRunnerSummary($keyType);
            }
        }
        return $rows;
    }

    public function getStagePoints(): array
    {
        $points = [];
        foreach ($this->_stages as $stageOrder => $stageRunners) {
            $points[$stageOrder] = $stageRunners->getPointsInStage();
        }
        return $points;
    }

    public function toArray()
    {
        $stages = [];
        foreach ($this->_stages as $key => $stage) {
            $stages[$key] = $stage->toArray();
        }
        return [
            'club' => $this->getClubName(),
            'points' => $this->getPointsPerClub(),
            'stages' => $stages,
        ];
    }

    public function getPointsPerClub()
    {
        $points = 0;
        foreach ($this->_stages as $stageRunners) {
            $points += $stageRunners->getPointsInStage();
        }
        return $points;
    }
}
