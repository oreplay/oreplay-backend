<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Table\EventsTable;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;

class EventsTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        FederationsFixture::LOAD,
        StagesFixture::LOAD,
    ];
    /** @var RunnersTable Runners */
    private $Events;

    public function setUp(): void
    {
        parent::setUp();
        $this->Events = EventsTable::load();
    }

    public function testFindPaginatedEvents(): void
    {
        // today
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
