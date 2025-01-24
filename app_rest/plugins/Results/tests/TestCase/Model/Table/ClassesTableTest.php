<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;

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
            'short_name' => 'ME',
            'long_name' => 'M Elite',
        ];
        $this->assertEquals($expected, $class->toArray());

        $class = $this->Classes->getByShortName(Event::FIRST_EVENT, Stage::FIRST_STAGE, 'x');
        $this->assertNull($class);

        $class = $this->Classes->getByShortName(Event::FIRST_EVENT, Stage::FIRST_STAGE, 'x');
        $this->assertNull($class);
    }

    public function testCreateIfNotExists()
    {
        $data = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '10',
            'short_name' => 'E',
            'long_name' => 'Elite',
            'course' => [
                'id' => '',
                'uuid' => '',
                'distance' => '5660.0',
                'climb' => '280.0',
                'controls' => '22',
                'oe_key' => '26',
                'short_name' => 'E'
            ],
            'runners' => []
        ];
        $res = $this->Classes->createIfNotExists(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2, $data);
        $this->assertEquals($data['oe_key'], $res->oe_key);
        $this->assertEquals($data['short_name'], $res->short_name);
        $this->assertEquals($data['long_name'], $res->long_name);
    }
}
