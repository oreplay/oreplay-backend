<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;

class StagesControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
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
        $this->assertEquals($expected, ($bodyDecoded['data']));
    }

    public function testGetData()
    {
        $this->get($this->_getEndpoint() . StagesFixture::STAGE_FEDO_2);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'id' => StagesFixture::STAGE_FEDO_2,
            'description' => 'Second stage',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/runners/'
            ],
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }
}
