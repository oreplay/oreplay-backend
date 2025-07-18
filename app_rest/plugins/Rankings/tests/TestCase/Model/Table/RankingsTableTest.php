<?php

declare(strict_types = 1);

namespace Rankings\Test\Model\Table;

use Cake\TestSuite\TestCase;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Lib\Consts\StatusCode;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;

class RankingsTableTest extends TestCase
{
    protected $fixtures = [
        RankingsFixture::LOAD,
    ];

    private RankingsTable $Rankings;

    public function setUp(): void
    {
        parent::setUp();
        $this->Rankings = RankingsTable::load();
    }

    public function testGetCached(): void
    {
        $ranking = $this->Rankings->getCached(RankingsTable::FIRST_RANKING);

        $this->assertEquals(100.0, $ranking->_getMaxPoints());
        $this->assertEquals(1, $ranking->_getRoundPrecision());
        $this->assertEquals(0.0, $ranking->_getNcScore(true));
        $this->assertTrue(0.0 === $ranking->_getNcScore(true));
        $this->assertTrue(null === $ranking->_getNcScore(false));
        $this->assertTrue(null === $ranking->getStatusScore('anything'));
        $this->assertTrue(null === $ranking->getStatusScore(StatusCode::OK));
        $this->assertTrue(0.0 === $ranking->getStatusScore(StatusCode::DNS));
        $this->assertTrue(10.0 === $ranking->getStatusScore(StatusCode::DNF));
        $this->assertTrue(10.0 === $ranking->getStatusScore(StatusCode::MP));
        $this->assertTrue(0.0 === $ranking->getStatusScore(StatusCode::DQF));
        $this->assertTrue(10.0 === $ranking->getStatusScore(StatusCode::OT));
        $this->assertEquals(EventsFixture::EVENT_TOMORROW_RANKING, $ranking->getEventId());
        $this->assertEquals(StagesFixture::STAGE_RANKING, $ranking->getStageId());
        $excluded = ['O NEGRO F', 'PROM'];
        $this->assertEquals($excluded, $ranking->getExcludedClassNames());
    }
}
