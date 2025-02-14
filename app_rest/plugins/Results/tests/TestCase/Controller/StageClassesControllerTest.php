<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;

class StageClassesControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClassesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/classes/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            [
                'id' => ClassEntity::FE,
                'short_name' => 'FE',
                'long_name' => 'F Elite',
            ],
            [
                'id' => ClassEntity::ME,
                'short_name' => 'ME',
                'long_name' => 'M Elite',
            ],
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }
}
