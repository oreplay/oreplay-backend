<?php

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Federation;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;

class EventsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $expected = $this->_getFirstEvent();
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    public function testGetData()
    {
        $this->get($this->_getEndpoint() . EventsFixture::EVENT_ID);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getFirstEvent();
        $expected['federation'] = [
            'id' => Federation::FEDO,
            'description' => 'FEDO SICO',
            'created' => '2023-01-01T10:01:00+00:00',
            'modified' => '2023-01-01T10:01:00+00:00',
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    private function _getFirstEvent(): array
    {
        return [
            'id' => EventsFixture::EVENT_ID,
            'description' => 'Test event',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => Federation::FEDO,
            'created' => '2022-03-01T10:01:00+00:00',
            'modified' => '2022-03-01T10:01:00+00:00',
        ];
    }
}
