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
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstEvent(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][1]);
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
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/runners/'
                ],
            ],
            [
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/runners/'
                ],
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
            'description' => 'Test Foot-o',
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

    private function _getSecondEvent(): array
    {
        return [
            'id' => EventsFixture::FIRST_RAID,
            'description' => 'Test Adventure Race',
            'initial_date' => '2024-01-26',
            'final_date' => '2024-01-26',
            'federation_id' => Federation::IOF,
            'created' => '2022-03-07T10:01:00+00:00',
            'modified' => '2022-03-07T10:01:00+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00'
            ]
        ];
    }

    public function testGetData_shouldReturnRaid()
    {
        $this->get($this->_getEndpoint() . EventsFixture::FIRST_RAID);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getSecondEvent();
        $expected['stages'] = [
            [
                'id' => StagesFixture::STAGE_RAID,
                'description' => 'Stage raid',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3',
                    'results' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3/teams/'
                ],
            ],
        ];
        $expected['federation'] = [
            'id' => Federation::IOF,
            'description' => 'IOF OEVENTOR',
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }
}
