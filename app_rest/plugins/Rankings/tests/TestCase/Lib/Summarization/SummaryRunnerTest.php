<?php

declare(strict_types = 1);

namespace Rankings\Test\Lib\Summarization;

use Cake\TestSuite\TestCase;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Lib\Summarization\Settings\AgeGroupDefinitions;
use Rankings\Lib\Summarization\SummaryRunner;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;

class SummaryRunnerTest extends TestCase
{
    private const string CLASS_WEIGHTED_BY_0_8 = 'M-21';

    protected array $fixtures = [
        RankingsFixture::LOAD,
        StageOrdersFixture::LOAD,
    ];

    private function _ageGroupDefinitions(): AgeGroupDefinitions
    {
        $flooringRanking = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        return new AgeGroupDefinitions(new SimpleScoreCalculator($flooringRanking));
    }

    private function _runnerWithRacePoints(array $pointsByStageOrder, bool $isNc = false): Runner
    {
        $results = [];
        foreach ($pointsByStageOrder as $stageOrder => $points) {
            $results[] = new RunnerResult([
                'stage_id' => StagesFixture::STAGE_RANKING,
                'stage_order' => $stageOrder,
                'result_type_id' => ResultType::PARTIAL_OVERALL,
                'points_final' => $points,
            ]);
        }
        return new Runner([
            'first_name' => 'Ana',
            'last_name' => 'Pérez Gómez',
            'is_nc' => $isNc,
            'class' => new ClassEntity(['short_name' => self::CLASS_WEIGHTED_BY_0_8]),
            'runner_results' => $results,
        ]);
    }

    public function testGetAgeGroup_shouldComeFromTheClass()
    {
        $summary = new SummaryRunner($this->_runnerWithRacePoints([1 => 87]), $this->_ageGroupDefinitions(), 1);

        $this->assertSame('absolute', $summary->getAgeGroup());
        $this->assertSame(5, $summary->getAmountOfRunners());
    }

    public function testGetComputablePoints_shouldWeightTheRaceByTheClassAndRoundLikeTheRanking()
    {
        $runner = $this->_runnerWithRacePoints([1 => 87, 2 => 100]);

        $firstRace = new SummaryRunner($runner, $this->_ageGroupDefinitions(), 1);
        $secondRace = new SummaryRunner($runner, $this->_ageGroupDefinitions(), 2);

        $this->assertSame(69.0, $firstRace->getComputablePoints(), '87 * 0.8 = 69.6, floored by the ranking');
        $this->assertSame(80.0, $secondRace->getComputablePoints());
    }

    public function testGetComputablePoints_shouldBeNullForARaceTheRunnerDidNotRun()
    {
        $summary = new SummaryRunner($this->_runnerWithRacePoints([1 => 87]), $this->_ageGroupDefinitions(), 2);

        $this->assertNull($summary->getComputablePoints());
    }

    public function testGetComputablePoints_shouldBeNullForANonCompetingRunner()
    {
        $nc = $this->_runnerWithRacePoints([1 => 87], true);

        $summary = new SummaryRunner($nc, $this->_ageGroupDefinitions(), 1);

        $this->assertNull($summary->getComputablePoints());
    }

    public function testGetComputablePoints_shouldBeNullOnceExcluded()
    {
        $summary = new SummaryRunner($this->_runnerWithRacePoints([1 => 87]), $this->_ageGroupDefinitions(), 1);

        $summary->setExcludedFromPoints();

        $this->assertNull($summary->getComputablePoints());
    }

    public function testToSummary_shouldShowPointsClassAndFirstName()
    {
        $summary = new SummaryRunner($this->_runnerWithRacePoints([1 => 87]), $this->_ageGroupDefinitions(), 1);

        $this->assertSame('69 (M-21) Ana', $summary->toSummary());
    }
}
