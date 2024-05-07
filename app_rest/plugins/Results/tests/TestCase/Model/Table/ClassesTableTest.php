<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;

class ClassesTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
    ];
    /** @var ClassesTable Runners */
    private $Classes;

    public function setUp(): void
    {
        parent::setUp();
        $this->Classes = ClassesTable::load();
    }

    public function testGetByShortName(): void
    {
        $class = $this->Classes->getByShortName(Event::FIRST_EVENT, Stage::FIRST_STAGE, 'ME');

        $expected = [
            'id' => 'd8a87faf-68a4-487b-8f28-6e0ead6c1a57',
            'short_name' => 'ME'
        ];
        $this->assertEquals($expected, $class->toArray());

        $class = $this->Classes->getByShortName(Event::FIRST_EVENT, Stage::FIRST_STAGE, 'x');
        $this->assertNull($class);

        $class = $this->Classes->getByShortName(Event::FIRST_EVENT, Stage::FIRST_STAGE, 'x');
        $this->assertNull($class);
    }
}
