<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Table\EventsTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;

class EventsTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        FederationsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
    ];
    /** @var RunnersTable Runners */
    private $Events;

    public function setUp(): void
    {
        parent::setUp();
        $this->Events = EventsTable::load();
    }

    public function testPatchFromNewValidatingFederation()
    {
        $data = [
            'description' => 'My new event',
        ];
        $res = $this->Events->patchFromNewValidatingFederation($data);
        $this->assertEquals($data['description'], $res->description);
        $this->assertEquals(36, strlen($res->id));
        // with uuid
        $data['id'] = '788a5cca-e93c-4a45-ba9d-a95cae6e5b19';
        $res = $this->Events->patchFromNewValidatingFederation($data);
        $this->assertEquals($data['description'], $res->description);
        $this->assertEquals($data['id'], $res->id);
        // with bad uuid
        $data['id'] = 'bad_format_uuid';
        $this->expectExceptionMessage('ID must be in UUID format ISO 9834 or not provided');
        $this->Events->patchFromNewValidatingFederation($data);
    }

    public function testGetEventWithRelations()
    {
        /** @var Event $res */
        $res = $this->Events->getEventWithRelations(Event::FIRST_EVENT);
        $this->assertEquals(Event::FIRST_EVENT, $res->id);
        $this->assertEquals('Test Foot-o', $res->description);
        $this->assertEquals(Federation::FEDO, $res->federation->id);
        $this->assertEquals('FEDO SICO', $res->federation->description);
        $this->assertEquals(Stage::FIRST_STAGE, $res->stages[0]->id);
        $stage = $res->stages[0];
        $this->assertEquals('First stage', $stage->description);
        $this->assertEquals(StageType::CLASSIC, $stage->stage_type_id);
        $this->assertEquals(StageType::CLASSIC, $stage->stage_type->id);
        $this->assertEquals('Foot-O, MTBO, Ski-O', $stage->stage_type->description);
    }

    public function testFindPaginatedEvents(): void
    {
        // today as date
        $filters = [
            'initial_date:lte' => date('Y-m-d'),
            'final_date:gte' => date('Y-m-d'),
        ];
        $events = $this->Events->findPaginatedEvents($filters)->all();
        $this->assertEquals(1, $events->count());
        $this->assertEquals(EventsFixture::EVENT_TODAY, $events->first()->id);

        // today as word
        $filters = [
            'when' => 'today',
        ];
        $events = $this->Events->findPaginatedEvents($filters)->all();
        $this->assertEquals(1, $events->count());
        $this->assertEquals(EventsFixture::EVENT_TODAY, $events->first()->id);

        // do not return hidden by default
        $this->Events->updateAll(['is_hidden' => true], ['id' => EventsFixture::EVENT_TODAY]);
        $events = $this->Events->findPaginatedEvents($filters)->all();
        $this->assertEquals(0, $events->count());

        // include hidden
        $filters['show_hidden'] = '1';
        $events = $this->Events->findPaginatedEvents($filters)->all();
        $this->assertEquals(1, $events->count());
        $this->assertEquals(EventsFixture::EVENT_TODAY, $events->first()->id);
    }
}
