<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\StageType;
use Results\Model\Table\StagesTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;

class StagesTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
    ];
    private StagesTable $Stages;

    public function setUp(): void
    {
        parent::setUp();
        $this->Stages = StagesTable::load();
    }

    public function testGetOrCreateTotalsInEvent(): void
    {
        // create new
        $stage1 = $this->Stages->getOrCreateTotalsInEvent(Event::FIRST_EVENT);
        $this->assertEquals(StageType::TOTALS, $stage1->stage_type_id);
        $this->assertEquals(StageType::TOTALS, $stage1->stage_type->id);
        $this->assertEquals('Totals', $stage1->stage_type->description);
        $this->assertEquals('', $stage1->description);
        $this->assertTrue($stage1->created->isToday());
        // existing one
        $stage2 = $this->Stages->getOrCreateTotalsInEvent(Event::FIRST_EVENT);
        $this->assertEquals(StageType::TOTALS, $stage2->stage_type_id);
        $this->assertEquals(StageType::TOTALS, $stage2->stage_type->id);
        $this->assertEquals('Totals', $stage2->stage_type->description);
        $this->assertEquals('', $stage2->description);
        $this->assertTrue($stage2->created->isToday());
        $this->assertEquals($stage1->id, $stage2->id);
    }
}
