<?php

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;

class EventsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
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
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getFirstEvent();
        $expected['stages'] = [
            [
                'id' => Stage::FIRST_STAGE,
                'description' => 'First stage',
            ],
            [
                'id' => '8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                'description' => 'Second stage',
            ],
        ];
        $expected['federation'] = [
            'id' => Federation::FEDO,
            'description' => 'FEDO SICO',
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    private function _getFirstEvent(): array
    {
        return [
            'id' => Event::FIRST_EVENT,
            'description' => 'Test event',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => Federation::FEDO,
            'created' => '2022-03-01T10:01:00+00:00',
            'modified' => '2022-03-01T10:01:00+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9'
            ]
        ];
    }
}
