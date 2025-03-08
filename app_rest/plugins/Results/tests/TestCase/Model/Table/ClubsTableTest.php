<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClubsTable;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;

class ClubsTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClubsFixture::LOAD,
    ];
    /** @var ClubsTable Runners */
    private ClubsTable $Clubs;

    public function setUp(): void
    {
        parent::setUp();
        $this->Clubs = ClubsTable::load();
    }

    public function testCreateIfNotExists(): void
    {
        $clubArray = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '1769',
            'long_name' => 'Valencia VALENCIA-O'
        ];
        $club = $this->Clubs->createIfNotExists(Event::FIRST_EVENT, Stage::FIRST_STAGE, $clubArray);
        $this->assertEquals('1769', $club->oe_key);
        $this->assertEquals('Valencia VALENCIA-O', $club->long_name);
        $this->assertEquals('Valencia VALENCIA-O', $club->short_name);
    }
}
