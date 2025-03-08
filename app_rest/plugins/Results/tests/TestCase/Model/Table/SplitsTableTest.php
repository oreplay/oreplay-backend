<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Split;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\SplitsFixture;

class SplitsTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        SplitsFixture::LOAD,
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
            'order_number' => null
        ];
        $this->assertEquals($expected, $array);
    }

    public function testDeleteAllByRunnerId()
    {
        /** @var Split $res */
        $res = $this->Splits->deleteAllByRunnerId(Runner::FIRST_RUNNER);
        $this->assertEquals(2, $res);

        $res = $this->Splits->findById(SplitsFixture::SPLIT_1)->first();
        $this->assertNull($res);
    }
}
