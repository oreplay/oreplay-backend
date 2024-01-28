<?php

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Test\Fixture\EventsFixture;

class EventsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/public/events/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $expected = [
            'id' => 1,
            'description' => 'Test event',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => null,
            'created' => '2022-03-01T10:01:00+00:00',
            'modified' => '2022-03-01T10:01:00+00:00',
            'deleted' => null
        ];
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }
}
