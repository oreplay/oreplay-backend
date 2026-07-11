<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageOrder;
use Results\Model\Table\StageOrdersTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;
use Cake\I18n\FrozenTime;

class StageOrdersTableTest extends TestCase
{
    protected array $fixtures = [
        StageOrdersFixture::LOAD,
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
    ];
    private StageOrdersTable $StageOrders;

    public function setUp(): void
    {
        parent::setUp();
        $this->StageOrders = StageOrdersTable::load();
        $this->StageOrders->deleteCache(Stage::FIRST_STAGE);
    }

    public function testGetAllInStage(): void
    {
        $res = $this->StageOrders->getAllInStage(Stage::FIRST_STAGE);
        $this->assertEquals(1, $res->count());
        /** @var StageOrder $first */
        $first = $res->first();
        $this->assertEquals(StageOrdersFixture::STAGE_1, $first->id);
        $this->assertEquals('Long stage', $first->description);
        $this->assertEquals(1, $first->stage_order);
    }

    public function testGetDescriptionByOrder(): void
    {
        $first = $this->StageOrders->getDescriptionByOrder(1, Stage::FIRST_STAGE);
        $this->assertEquals(StageOrdersFixture::STAGE_1, $first->id);
        $this->assertEquals('Long stage', $first->description);
        $this->assertEquals(1, $first->stage_order);
    }

    public function testGetAllCreatingOne(): void
    {
        $res = $this->StageOrders->getAllCreatingOne(Stage::FIRST_STAGE, Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertEquals(1, $res->count());
        /** @var StageOrder $first */
        $first = $res->first();
        $this->assertEquals(StageOrdersFixture::STAGE_1, $first->id);
        $this->assertEquals('Long stage', $first->description);
        $this->assertEquals(1, $first->stage_order);
        $this->assertEquals(Stage::FIRST_STAGE, $first->original_stage_id);
        // add one more
        $res = $this->StageOrders
            ->getAllCreatingOne(StagesFixture::STAGE_RAID, Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertEquals(2, $res->count());
        /** @var StageOrder $first */
        $first = $res->first();
        $this->assertEquals(StageOrdersFixture::STAGE_1, $first->id);
        $this->assertEquals('Long stage', $first->description);
        $this->assertEquals(1, $first->stage_order);
        $this->assertEquals(Stage::FIRST_STAGE, $first->original_stage_id);
        /** @var StageOrder $last */
        $last = $res->last();
        $this->assertEquals('Test Adventure Race', $last->description);
        $this->assertEquals(2, $last->stage_order);
        $this->assertEquals(StagesFixture::STAGE_RAID, $last->original_stage_id);
        $this->assertEquals(EventsFixture::FIRST_RAID, $last->original_event_id);
        $this->assertTrue($last->is_official);
        $this->assertInstanceOf(FrozenTime::class, $last->computed);
        $this->assertNull($last->start); // STAGE_RAID has no start
    }

    public function testGetAllCreatingOneCopiesSourceStageStart(): void
    {
        $res = $this->StageOrders
            ->getAllCreatingOne(StagesFixture::STAGE_FEDO_2, Event::FIRST_EVENT, Stage::FIRST_STAGE);
        /** @var StageOrder $created */
        $created = $res->last();
        $this->assertEquals(StagesFixture::STAGE_FEDO_2, $created->original_stage_id);
        $this->assertEquals(Event::FIRST_EVENT, $created->original_event_id);
        $this->assertEquals('2023-11-01 10:02:00', $created->start->format('Y-m-d H:i:s'));
    }

    public function testTruncateDescriptionClipsMultibyteToColumnLength(): void
    {
        $maxLen = StageOrdersTable::DESCRIPTION_MAX_LENGTH;
        $tooLong = str_repeat('ñ', $maxLen + 50);

        $truncated = StageOrdersTable::truncateDescription($tooLong);

        $this->assertSame($maxLen, mb_strlen($truncated));
        $this->assertSame(mb_substr($tooLong, 0, $maxLen), $truncated);
        // still valid utf-8: no multibyte character was split at the boundary
        $this->assertSame($truncated, mb_convert_encoding($truncated, 'UTF-8', 'UTF-8'));
    }
}
