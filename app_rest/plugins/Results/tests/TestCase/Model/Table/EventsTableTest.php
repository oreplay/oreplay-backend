<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\EventsTable;
use Results\Model\Table\UploadLogsTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UploadLogsFixture;

class EventsTableTest extends TestCase
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        FederationsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        UploadLogsFixture::LOAD,
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
        $stage = $res->stages[0];
        $this->assertEquals(Stage::FIRST_STAGE, $stage->id);
        $this->assertEquals(UploadLog::STATE_START, $stage->_getLastLogs()[0]['state']);
        $this->assertEquals('First stage', $stage->description);
        $this->assertEquals(StageType::CLASSIC, $stage->stage_type_id);
        $this->assertEquals(StageType::CLASSIC, $stage->stage_type->id);
        $this->assertEquals('Foot-O, MTBO, Ski-O', $stage->stage_type->description);
    }

    private function _addLog(string $stageId, int $state, string $created): void
    {
        $logs = UploadLogsTable::load();
        $log = $logs->newEmptyEntity();
        $log->id = \Cake\Utility\Text::uuid();
        $log->event_id = Event::FIRST_EVENT;
        $log->stage_id = $stageId;
        $log->state = $state;
        $log->created = new \Cake\I18n\FrozenTime($created);
        $logs->saveOrFail($log);
    }

    public function testGetEventWithRelationsKeepsOnlyTheNewestLogOfEachState()
    {
        $this->_addLog(Stage::FIRST_STAGE, UploadLog::STATE_START, '2024-01-02 11:00:00');
        $this->_addLog(Stage::FIRST_STAGE, 2, '2024-01-02 12:00:00');
        $this->_addLog(Stage::FIRST_STAGE, 2, '2024-01-02 13:00:00');
        $this->_addLog(StagesFixture::STAGE_FEDO_2, 2, '2024-01-02 09:00:00');

        $res = $this->Events->getEventWithRelations(Event::FIRST_EVENT);

        $stage = $res->stages[0];
        $this->assertEquals(Stage::FIRST_STAGE, $stage->id);
        $logs = $stage->_getLastLogs();
        $this->assertCount(2, $logs);
        $this->assertEquals(UploadLog::STATE_START, $logs[0]->state);
        $this->assertEquals('2024-01-02 11:00:00', $logs[0]->created->format('Y-m-d H:i:s'));
        $this->assertEquals(2, $logs[1]->state);
        $this->assertEquals('2024-01-02 13:00:00', $logs[1]->created->format('Y-m-d H:i:s'));
        // the newest of one stage must not leak into another
        $other = array_values(array_filter($res->stages, fn($s) => $s->id === StagesFixture::STAGE_FEDO_2))[0];
        $this->assertCount(1, $other->_getLastLogs());
        $this->assertEquals('2024-01-02 09:00:00', $other->_getLastLogs()[0]->created->format('Y-m-d H:i:s'));
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

        // show_hidden is ignored for anonymous callers
        $filters['show_hidden'] = '1';
        $events = $this->Events->findPaginatedEvents($filters)->all();
        $this->assertEquals(0, $events->count());

        // admins see hidden events with show_hidden
        $events = $this->Events->findPaginatedEvents($filters, null, true)->all();
        $this->assertEquals(1, $events->count());
        $this->assertEquals(EventsFixture::EVENT_TODAY, $events->first()->id);

        // search by description
        $events = $this->Events->findPaginatedEvents(['description' => 'Adventure'])->all();
        $this->assertEquals(1, $events->count());
        $this->assertEquals(EventsFixture::FIRST_RAID, $events->first()->id);
    }
}
