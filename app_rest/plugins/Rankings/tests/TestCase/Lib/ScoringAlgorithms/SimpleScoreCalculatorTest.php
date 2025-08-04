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

    public function testHasFewComputable()
    {
        $settings = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        $calc = new SimpleScoreCalculator($settings);

        $parts = 1;
        $this->assertFalse($calc->hasFewComputable($parts, 2));
        $this->assertFalse($calc->hasFewComputable($parts, 3));
        $this->assertTrue($calc->hasFewComputable($parts, 4));

        $parts = 2;
        $this->assertFalse($calc->hasFewComputable($parts, 2));
        $this->assertFalse($calc->hasFewComputable($parts, 3));
        $this->assertFalse($calc->hasFewComputable($parts, 4));
        $this->assertFalse($calc->hasFewComputable($parts, 5));
        $this->assertFalse($calc->hasFewComputable($parts, 6));
        $this->assertTrue($calc->hasFewComputable($parts, 7));

        $parts = 3;
        $this->assertFalse($calc->hasFewComputable($parts, 2));
        $this->assertFalse($calc->hasFewComputable($parts, 3));
        $this->assertFalse($calc->hasFewComputable($parts, 4));
        $this->assertFalse($calc->hasFewComputable($parts, 5));
        $this->assertFalse($calc->hasFewComputable($parts, 6));
        $this->assertFalse($calc->hasFewComputable($parts, 7));
        $this->assertFalse($calc->hasFewComputable($parts, 8));
        $this->assertFalse($calc->hasFewComputable($parts, 9));
        $this->assertTrue($calc->hasFewComputable($parts, 10));
        $this->assertTrue($calc->hasFewComputable($parts, 11));
    }

    public function testGetOrgComputable()
    {
        $settings = RankingsTable::load()->getCached(RankingsTable::FIRST_RANKING);
        $calc = new SimpleScoreCalculator($settings);

        // with few computable
        $hasFewComputable = 9999;
        $parts = 1;
        $this->assertEquals(1, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 2;
        $this->assertEquals(2, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 4;
        $this->assertEquals(4, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 6;
        $this->assertEquals(6, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 8;
        $this->assertEquals(8, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 10;
        $this->assertEquals(10, $calc->getOrgComputable($parts, $hasFewComputable));
        $parts = 12;
        $this->assertEquals(12, $calc->getOrgComputable($parts, $hasFewComputable));

        // without few computable
        $this->assertEquals(1, $calc->getOrgComputable(1, 2));
        $this->assertEquals(1, $calc->getOrgComputable(2, 3));
        $this->assertEquals(1, $calc->getOrgComputable(3, 4));
        $this->assertEquals(2, $calc->getOrgComputable(4, 5));

        $this->assertEquals(1, $calc->getOrgComputable(1, 6));
        $this->assertEquals(2, $calc->getOrgComputable(2, 6));
        $this->assertEquals(2, $calc->getOrgComputable(3, 6));
        $this->assertEquals(2, $calc->getOrgComputable(4, 6));
        $this->assertEquals(2, $calc->getOrgComputable(5, 6));
        $this->assertEquals(2, $calc->getOrgComputable(6, 6));

        $this->assertEquals(2, $calc->getOrgComputable(6, 7));

        $this->assertEquals(1, $calc->getOrgComputable(1, 8));
        $this->assertEquals(2, $calc->getOrgComputable(2, 8));
        $this->assertEquals(2, $calc->getOrgComputable(3, 8));
        $this->assertEquals(2, $calc->getOrgComputable(4, 8));// real case
        $this->assertEquals(2, $calc->getOrgComputable(5, 8));
        $this->assertEquals(2, $calc->getOrgComputable(6, 8));
        $this->assertEquals(2, $calc->getOrgComputable(7, 8));
        $this->assertEquals(2, $calc->getOrgComputable(8, 8));

        $this->assertEquals(1, $calc->getOrgComputable(1, 9));
        $this->assertEquals(2, $calc->getOrgComputable(2, 9));
        $this->assertEquals(3, $calc->getOrgComputable(3, 9));
        $this->assertEquals(3, $calc->getOrgComputable(4, 9));
        $this->assertEquals(3, $calc->getOrgComputable(5, 9));
        $this->assertEquals(3, $calc->getOrgComputable(6, 9));
        $this->assertEquals(3, $calc->getOrgComputable(7, 9));
        $this->assertEquals(3, $calc->getOrgComputable(8, 9));
        $this->assertEquals(3, $calc->getOrgComputable(9, 9));

        $this->assertEquals(3, $calc->getOrgComputable(9, 10));
        $this->assertEquals(3, $calc->getOrgComputable(10, 11));
        $this->assertEquals(4, $calc->getOrgComputable(11, 12));
        $this->assertEquals(4, $calc->getOrgComputable(12, 13));
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
            'status_code' => null,
            'is_nc' => null,
            'contributory' => null,
            'time_seconds' => null,
            'time_behind' => null,
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
            'status_code' => null,
            'is_nc' => null,
            'contributory' => null,
            'time_seconds' => 0,
            'time_behind' => null,
            'points_final' => 132,
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
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => true,
                    'time_seconds' => 0,
                    'time_behind' => null,
                    'points_final' => 50,
                    'note' => null,
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => null,
                ],
                [
                    'id' => '',
                    'stage_order' => 2,
                    'upload_type' => UploadTypes::COMPUTABLE_ORGANIZER,
                    'stage' => null,
                    'position' => null,
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => true,
                    'time_seconds' => 0,
                    'time_behind' => null,
                    'points_final' => 66,
                    'note' => null,
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => null,
                ],
                [
                    'id' => '',
                    'stage_order' => 3,
                    'upload_type' => null,
                    'stage' => null,
                    'position' => 4,
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => true,
                    'time_seconds' => 0,
                    'time_behind' => null,
                    'points_final' => 82.5,
                    'note' => null,
                    'status_code' => null,
                    'is_nc' => null,
                    'contributory' => null,
                ],
            ],
            'overall' => [
                'id' => '',
                'stage_order' => 3,
                'stage' => null,
                'position' => ScoringAlgorithm::NEEDS_POSITION,
                'status_code' => null,
                'is_nc' => null,
                'contributory' => null,
                'time_seconds' => 0,
                'time_behind' => null,
                'points_final' => 198,
                'upload_type' => UploadTypes::RANKING_COMPUTED,
                'status_code' => null,
                'is_nc' => null,
                'contributory' => null,
                'note' => null,
            ]
        ];
        $this->assertEquals($expected, json_decode(json_encode($overalls), true));
    }
}
