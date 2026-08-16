<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\TeamResultsFixture;

class SplitsTableTest extends TestCase
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        SplitsFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
    ];
    /** @var SplitsTable Runners */
    private $Splits;

    public function setUp(): void
    {
        parent::setUp();
        $this->Splits = SplitsTable::load();
    }

    public function testGet()
    {
        /** @var Split $res */
        $res = $this->Splits->get(SplitsFixture::SPLIT_1);

        $array = json_decode(json_encode($res), true);
        $expected = [
            'id' => SplitsFixture::SPLIT_1,
            'reading_time' => '2024-01-02T10:00:10.321+00:00',
            'points' => null,
            'is_intermediate' => false,
            'order_number' => null,
            'created' => '2024-01-02T10:00:10.000+00:00',
        ];
        $this->assertEquals($expected, $array);
    }

    public function testDeleteAllByResultIdsRemovesRunnerSplitsInOneStatement()
    {
        $deleted = $this->Splits->deleteAllByResultIds('runner_result_id', [RunnerResult::FIRST_RES]);

        $this->assertEquals(2, $deleted);
        $this->assertNull($this->Splits->findById(SplitsFixture::SPLIT_1)->first());
    }

    public function testDeleteAllByResultIdsRemovesTeamSplits()
    {
        $deleted = $this->Splits->deleteAllByResultIds('team_result_id', [TeamResultsFixture::TEAM_RESULT_1]);

        $this->assertEquals(1, $deleted);
        $this->assertNull($this->Splits->findById(SplitsFixture::SPLIT_2)->first());
    }

    public function testDeleteAllByResultIdsIgnoresAnEmptyList()
    {
        $this->assertEquals(0, $this->Splits->deleteAllByResultIds('runner_result_id', []));
        $this->assertNotNull($this->Splits->findById(SplitsFixture::SPLIT_1)->first());
    }
}
