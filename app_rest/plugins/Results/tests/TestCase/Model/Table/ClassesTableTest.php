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
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\CoursesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;

class ClassesTableTest extends TestCase
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
        StagesFixture::LOAD,
        ControlsFixture::LOAD,
        SplitsFixture::LOAD,
        CoursesFixture::LOAD,
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

    public function testGetByStageWithRadios_shouldReportNoRadiosWithoutAStoredCourse()
    {
        // there is no punch-derived fallback: a class shows radios once its course is stored or a radio
        // export declares them, and until then it shows none
        $split = new Split([
            'id' => '2e0a9e34-ad82-4f41-a46e-d76427705281',
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'sicard' => '8000001',
            'is_intermediate' => true,
            'station' => 31,
            'reading_time' => '2024-01-02 10:00:10.321',
            'control_id' => ControlsFixture::CONTROL_31,
            'class_id' => ClassEntity::ME,
            'created' => '2024-01-02 09:10:10',
            'modified' => '2024-01-02 09:10:10',
        ]);
        SplitsTable::load()->save($split);

        $res = $this->Classes
            ->getByStageWithRadios(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->toArray();

        $this->assertEquals(2, count($res));
        $this->assertEquals('FE', $res[0]['short_name']);
        $this->assertEquals('ME', $res[1]['short_name']);
        $this->assertEquals([], $res[0]['splits']);
        $this->assertEquals([], $res[1]['splits'], 'punched radios alone no longer make a radio list');
    }

    public function testGetByStageWithRadios_shouldReadTheCourseInsteadOfThePunches()
    {
        $course = CoursesTable::load()->get(CoursesFixture::COURSE_1);
        CourseControlsTable::load()->replaceForCourse($course, ['82', '31', '81']);
        ControlsTable::load()->markIntermediateStations(Stage::FIRST_STAGE, ['31', '81']);
        $this->Classes->updateAll(['course_id' => $course->id], ['id' => ClassEntity::ME]);

        $res = $this->Classes
            ->getByStageWithRadios(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->toArray();

        $this->assertEquals('ME', $res[1]['short_name']);
        $expected = [
            ['id' => ControlsFixture::CONTROL_31, 'station' => '31'],
            ['id' => ControlsFixture::CONTROL_81, 'station' => '81'],
        ];
        $actual = array_map(fn($radio) => $radio->toArray(), $res[1]['splits']);
        $this->assertEquals($expected, $actual,
            'station 82 is in the course but carries no radio, and 81 has a radio but no punch');
    }

    public function testGetByStageWithRadios_shouldListTheDeclaredRadiosInTheirOrderWithoutAStoredCourse()
    {
        ControlsTable::load()->markIntermediateStations(Stage::FIRST_STAGE, ['31', '81']);
        $this->Classes->updateAll(['radio_stations' => '81,82,31'], ['id' => ClassEntity::ME]);

        $res = $this->Classes
            ->getByStageWithRadios(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->toArray();

        $this->assertEquals('ME', $res[1]['short_name']);
        $expected = [
            ['id' => ControlsFixture::CONTROL_81, 'station' => '81'],
            ['id' => ControlsFixture::CONTROL_31, 'station' => '31'],
        ];
        $actual = array_map(fn($radio) => $radio->toArray(), $res[1]['splits']);
        $this->assertEquals($expected, $actual,
            'the declared order is kept, and 82 is left out because it is not a radio of the stage');
        $this->assertEquals([], $res[0]['splits'], 'a class that declares nothing shows nothing');
    }

    public function testGetByStageWithRadios_shouldPreferTheStoredCourseOverTheDeclaredRadios()
    {
        $course = CoursesTable::load()->get(CoursesFixture::COURSE_1);
        CourseControlsTable::load()->replaceForCourse($course, ['82', '31', '81']);
        ControlsTable::load()->markIntermediateStations(Stage::FIRST_STAGE, ['31', '81']);
        $this->Classes->updateAll(
            ['course_id' => $course->id, 'radio_stations' => '81,31'],
            ['id' => ClassEntity::ME]
        );

        $res = $this->Classes
            ->getByStageWithRadios(Event::FIRST_EVENT, Stage::FIRST_STAGE)
            ->toArray();

        $actual = array_map(fn($radio) => (string)$radio->station, $res[1]['splits']);
        $this->assertEquals(['31', '81'], $actual, 'the course learned from the downloads is the order to trust');
    }
}
