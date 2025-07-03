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
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Club;
use Results\Model\Entity\Overalls;
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

            public function toArrayWithoutID(): array
            {
                return $this->jsonSerialize();
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

    public function testCalculateOverallScore()
    {
        $overalls = new Overalls();
        $settings = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        $calc = new SimpleScoreCalculator($settings);
        // empty
        $overalls = $calc->calculateOverallScore($overalls);
        $expected = [
            'id' => '',
            'stage_order' => null,
            'stage' => null,
            'position' => null,
            'time_seconds' => null,
            'points_final' => null,
            'upload_type' => null,
            'note' => null,
        ];
        $this->assertEquals($expected, $overalls->_getOverall()->toArray());
        // 2 results
        $overalls->setParts([
            PartialOverall::fromValues(1, 2, 0, 50),
            PartialOverall::fromValues(2, 4, 0, 82.5),
        ]);
        $overalls = $calc->calculateOverallScore($overalls);
        $expected = [
            'id' => '',
            'stage_order' => 2,
            'stage' => null,
            'position' => ScoringAlgorithm::NEEDS_POSITION,
            'time_seconds' => 0,
            'points_final' => 132.5,
            'upload_type' => UploadTypes::RANKING_COMPUTED,
            'note' => null,
        ];
        $this->assertEquals($expected, $overalls->_getOverall()->toArray());
        // 2 results with organizer
        $organizer = PartialOverall::fromValues(2);
        $organizer->upload_type = UploadTypes::COMPUTABLE_ORGANIZER;
        $overalls->setParts([
            PartialOverall::fromValues(1, 2, 0, 50),
            $organizer,
            PartialOverall::fromValues(3, 4, 0, 82.5),
        ]);
        $overalls = $calc->calculateOverallScore($overalls);
        $expected = [
            'parts' => [
                [
                    'id' => '',
                    'stage_order' => 1,
                    'upload_type' => null,
                    'stage' => null,
                    'position' => 2,
                    'time_seconds' => 0,
                    'points_final' => 50,
                    'note' => null
                ],
                [
                    'id' => '',
                    'stage_order' => 2,
                    'upload_type' => UploadTypes::COMPUTABLE_ORGANIZER,
                    'stage' => null,
                    'position' => null,
                    'time_seconds' => 0,
                    'points_final' => 66.25,
                    'note' => null
                ],
                [
                    'id' => '',
                    'stage_order' => 3,
                    'upload_type' => null,
                    'stage' => null,
                    'position' => 4,
                    'time_seconds' => 0,
                    'points_final' => 82.5,
                    'note' => null
                ],
            ],
            'overall' => [
                'id' => '',
                'stage_order' => 3,
                'stage' => null,
                'position' => ScoringAlgorithm::NEEDS_POSITION,
                'time_seconds' => 0.0,
                'points_final' => 198.8,
                'upload_type' => UploadTypes::RANKING_COMPUTED,
                'note' => null,
            ]
        ];
        $this->assertEquals($expected, json_decode(json_encode($overalls), true));
    }
}
