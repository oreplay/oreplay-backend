<?php

declare(strict_types = 1);

namespace Rankings\Test\Lib\ScoringAlgorithms;

use Cake\TestSuite\TestCase;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Table\ParticipantInterface;
use Rankings\Model\Table\RankingsTable;
use Rankings\Model\Traits\ParticipantTrait;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\Club;
use Results\Model\Entity\PartialOverall;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\TeamResult;

class SimpleScoreCalculatorTest extends TestCase
{
    protected $traitObject;

    protected $fixtures = [
        RankingsFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->traitObject = new class implements ParticipantInterface {
            use ParticipantTrait;

            public $is_nc = false;

            public function _getClub(): ?Club
            {
                return null;
            }

            public function _getStage(): TeamResult|RunnerResult|null
            {
                $result = new RunnerResult();
                $result->time_seconds = 60;
                return $result;
            }

            public function getResultList()
            {
                return [];
            }
        };
    }

    public function testParticipantScore()
    {
        $settings = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        $calc = new SimpleScoreCalculator($settings);
        // winner 100 points
        $points = $calc->participantScore($this->traitObject, $this->traitObject);
        $this->assertEquals(100, $points);
        // nc 0 points
        $this->traitObject->is_nc = true;
        $points = $calc->participantScore($this->traitObject, $this->traitObject);
        $this->assertEquals(0, $points);
    }

    public function testGetOverallScore()
    {
        $settings = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        $calc = new SimpleScoreCalculator($settings);
        // empty
        $overall = $calc->calculateOverallScore([]);
        $expected = [
            'id' => '',
            'stage_order' => null,
            'stage' => null,
            'position' => null,
            'time_seconds' => null,
            'points_final' => null
        ];
        $this->assertEquals($expected, $overall->toArray());
        // 2 results
        $parts = [
            PartialOverall::fromValues(1, 2, 0, 50),
            PartialOverall::fromValues(2, 4, 0, 82.5),
        ];
        $overall = $calc->calculateOverallScore($parts);
        $expected = [
            'id' => '',
            'stage_order' => 2,
            'stage' => null,
            'position' => ScoringAlgorithm::NEEDS_POSITION,
            'time_seconds' => 0,
            'points_final' => 132.5
        ];
        $this->assertEquals($expected, $overall->toArray());
    }
}
