<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Split;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClassesTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\SplitsFixture;

class StageClassesControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
        SplitsFixture::LOAD,
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
            'splits' => [
                [
                    '_c' => Split::class,
                    'id' => SplitsFixture::SPLIT_1_RADIO,
                    'station' => 31,
                ]
            ],
        ];
        $this->assertEquals([$fe, $me], $bodyDecoded['data']);

        // test oe_key inverse order
        $Table->updateAll(['oe_key' => 101], ['id' => ClassEntity::FE]);
        $Table->updateAll(['oe_key' => 15], ['id' => ClassEntity::ME]);
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals([$me, $fe], $bodyDecoded['data']);
    }
}
