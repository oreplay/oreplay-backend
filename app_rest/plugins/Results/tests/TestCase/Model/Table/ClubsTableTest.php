<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClubsTable;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;

class ClubsTableTest extends TestCase
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
    ];
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

    public function testCopyClubs(): void
    {
        $this->assertEquals(1, $this->Clubs->find()->where(['stage_id' => Stage::FIRST_STAGE])->all()->count());
        $newStage = StagesFixture::STAGE_FEDO_2;
        $this->assertEquals(0, $this->Clubs->find()->where(['stage_id' => $newStage])->all()->count());

        $clubs = $this->Clubs->copyClubs(Stage::FIRST_STAGE, Event::FIRST_EVENT, $newStage);

        $this->assertEquals(1, $this->Clubs->find()->where(['stage_id' => $newStage])->all()->count());
        $this->assertEquals(1, count($clubs));
    }
}
