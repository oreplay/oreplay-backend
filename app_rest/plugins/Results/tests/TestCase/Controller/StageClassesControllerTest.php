<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Control;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ControlsTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\SplitsFixture;

class StageClassesControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/classes/';
    }

    public function testGetList()
    {
        $Table = ClassesTable::load();
        $Table->updateAll(['oe_key' => 15], ['id' => ClassEntity::FE]);
        $Table->updateAll(['oe_key' => 101], ['id' => ClassEntity::ME]);
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $fe = [
            '_c' => ClassEntity::class,
            'id' => ClassEntity::FE,
            'short_name' => 'FE',
            'long_name' => 'F Elite',
            'splits' => [],
        ];
        $me = [
            '_c' => ClassEntity::class,
            'id' => ClassEntity::ME,
            'short_name' => 'ME',
            'long_name' => 'M Elite',
            // a punched radio does not produce a list on its own: the class needs a stored course or
            // radios declared by an export
            'splits' => [],
        ];
        $this->assertEquals([$fe, $me], $bodyDecoded['data']);

        // test oe_key inverse order
        $Table->updateAll(['oe_key' => 101], ['id' => ClassEntity::FE]);
        $Table->updateAll(['oe_key' => 15], ['id' => ClassEntity::ME]);
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals([$me, $fe], $bodyDecoded['data']);
    }

    public function testGetList_shouldShowTheDeclaredRadiosWithoutExposingTheStoredList()
    {
        ControlsTable::load()->markIntermediateStations(Stage::FIRST_STAGE, ['31']);
        ClassesTable::load()->updateAll(['radio_stations' => '31'], ['id' => ClassEntity::ME]);
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $me = $bodyDecoded['data'][1];
        $this->assertEquals('ME', $me['short_name']);
        $this->assertEquals([[
            '_c' => Control::class,
            'id' => ControlsFixture::CONTROL_31,
            'station' => '31',
        ]], $me['splits']);
        foreach ($bodyDecoded['data'] as $class) {
            $this->assertArrayNotHasKey('radio_stations', $class,
                'the stored list is internal: deployed clients reject unknown properties and the spec must not change');
        }
    }
}
