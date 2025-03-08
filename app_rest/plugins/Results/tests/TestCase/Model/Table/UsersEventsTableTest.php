<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use App\Test\Fixture\UsersFixture;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\UsersEvent;
use Results\Model\Table\UsersEventsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\UsersEventsFixture;

class UsersEventsTableTest extends TestCase
{
    protected $fixtures = [
        UsersFixture::LOAD,
        EventsFixture::LOAD,
        UsersEventsFixture::LOAD,
    ];
    /** @var UsersEventsTable UsersEvent */
    private $UsersEvents;

    public function setUp(): void
    {
        parent::setUp();
        $this->UsersEvents = UsersEventsTable::load();
    }

    public function testCreate(): void
    {
        /** @var UsersEvent $entity */
        $entity = $this->UsersEvents->newEmptyEntity();
        $entity->user_id = UsersFixture::USER_ADMIN_ID;
        $entity->event_id = Event::FIRST_EVENT;
        $entity->created = '2022-03-01 10:01:01';
        $entity->modified = $entity->created;

        $saved = $this->UsersEvents->saveOrFail($entity);
        $this->assertEquals($entity->user_id, $saved->user_id);
        $db = $this->UsersEvents->find()->all();
        $this->assertEquals($entity->user_id, $db->first()->user_id);
    }
    public function testFindByUser(): void
    {
        /** @var UsersEvent $rel */
        $rel = $this->UsersEvents->find()
            ->where(['user_id' => UsersFixture::USER_ADMIN_ID])->first();
        $this->assertEquals(Event::FIRST_EVENT, $rel->event_id);
        $this->assertEquals(UsersFixture::USER_ADMIN_ID, $rel->user_id);
    }
}
