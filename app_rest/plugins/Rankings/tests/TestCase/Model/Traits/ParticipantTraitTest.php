<?php

declare(strict_types = 1);

namespace Rankings\Test\Model\Traits;

use Cake\TestSuite\TestCase;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\ParticipantInterface;
use Rankings\Model\Traits\ParticipantTrait;
use Results\Model\Entity\Club;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\TeamResult;

class ParticipantTraitTest extends TestCase
{
    protected $traitObject;

    protected $fixtures = [
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
        };
    }

    public function testGetRankingPoints()
    {
        // winner 100 points
        $this->traitObject
            ->setSettings(new Ranking())
            ->setLeader($this->traitObject);
        $points = $this->traitObject->_getRankingPoints();
        $this->assertEquals(100, $points);
        // nc 0 points
        $this->traitObject->is_nc = true;
        $points = $this->traitObject->_getRankingPoints();
        $this->assertEquals(0, $points);
    }
}
