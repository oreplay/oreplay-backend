<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Table;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;

class ClassesTableTest extends TestCase
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
        StagesFixture::LOAD,
        ControlsFixture::LOAD,
        SplitsFixture::LOAD,
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
            'id' => ClassEntity::ME,
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
        Cache::delete('getByShortName_Classes_bfc5bf7328fd8975addb36e3de885a03', CacheGrp::UPLOAD);
        $data = [
            'id' => '',
            'uuid' => '',
            'oe_key' => '10',
            'short_name' => 'E',
            'long_name' => 'Senior',
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
        $this->assertEquals($data['long_name'], $res->long_name);
        $this->assertEquals($data['oe_key'], $res->oe_key);
        $this->assertEquals($data['short_name'], $res->short_name);
    }

    public function testGetByStageWithRadios()
    {
        $split = new Split(
            [
                'id' => '2e0a9e34-ad82-4f41-a46e-d76427705281',
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'sicard' => '8000001',
                'is_intermediate' => true,
                'station' => 81,
                'reading_time' => '2024-01-02 10:00:10.321',
                'control_id' => ControlsFixture::CONTROL_31,
                'class_id' => ClassEntity::ME,
                'created' => '2024-01-02 09:10:10',
                'modified' => '2024-01-02 09:10:10',
            ]
        );
        SplitsTable::load()->save($split);
        $splitId = '2e0a9e34-ad82-4f41-a46e-d76427705282';
        $split = new Split(
            [
                'id' => $splitId,
                'event_id' => Event::FIRST_EVENT,
                'stage_id' => Stage::FIRST_STAGE,
                'sicard' => '8000002',
                'is_intermediate' => true,
                'station' => 182,
                'reading_time' => '2024-01-02 10:00:10.321',
                'control_id' => null,
                'class_id' => ClassEntity::ME,
                'created' => '2024-01-02 09:10:10',
                'modified' => '2024-01-02 09:10:10',
            ]
        );
        SplitsTable::load()->save($split);

        $res = $this->Classes
            ->getByStageWithRadios(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->toArray();
        $this->assertEquals(2, count($res));

        $this->assertEquals('FE', $res[0]['short_name']);
        $this->assertEquals('F Elite', $res[0]['long_name']);
        $this->assertEquals([], $res[0]['splits']);
        $this->assertEquals('ME', $res[1]['short_name']);
        $this->assertEquals('M Elite', $res[1]['long_name']);
        $this->assertEquals(2, count($res[1]['splits']));
        $first = [
            'id' => SplitsFixture::SPLIT_1_RADIO,
            'station' => '81'
        ];
        $this->assertEquals($first, $res[1]['splits'][0]->toArray());
        $second = [
            'id' => $splitId,
            'station' => '182'
        ];
        $this->assertEquals($second, $res[1]['splits'][1]->toArray());
    }
}
