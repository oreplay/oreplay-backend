<?php

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnersFixture;

class RunnersControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        RunnersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/runners/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $expected = $this->_getFirstRunner();
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    private function _getFirstRunner(): array
    {
        return [
            'id' => 'd08fa43b-ddf8-47f6-9a59-2f1828881765',
            'first_name' => 'First',
            'last_name' => 'Runner',
        ];
    }
}
