<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use DateTime;
use Results\Controller\EventsController;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Stage;
use Results\Model\Table\EventsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\UsersEventsFixture;

class EventsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        UsersEventsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(4, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstEvent(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][1]);
    }

    public function testGetList_paginated_NoParams()
    {
        $this->get($this->_getEndpoint() . '?page=2&limit=1');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_WhenToday()
    {
        $this->get($this->_getEndpoint() . '?when=today&show_hidden=1');
        $today = (new DateTime('now'))->format('Y-m-d');

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'id' => EventsFixture::EVENT_TODAY,
            'description' => 'Today event',
            'initial_date' => $today,
            'final_date' => $today,
            'federation_id' => 'IOF',
            'created' => '2022-03-10T10:01:00.000+00:00',
            'modified' => '2022-03-10T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/' . EventsFixture::EVENT_TODAY
            ]
        ];

        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_WhenFuture()
    {
        $this->get($this->_getEndpoint() . '?when=future');
        $tomorrow = (new DateTime('now'))->modify('+1 day')->format('Y-m-d');

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'id' => '1b10cfcc-b3f2-40bb-8dbe-8b2-tomorrow',
            'description' => 'Tomorrow event',
            'initial_date' => $tomorrow,
            'final_date' => $tomorrow,
            'federation_id' => 'IOF',
            'created' => '2022-03-13T10:01:00.000+00:00',
            'modified' => '2022-03-13T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8b2-tomorrow'
            ]
        ];
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_WhenPast()
    {
        $this->get($this->_getEndpoint() . '?when=past&page=2&limit=1');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][0]);
    }

    public function testGetData()
    {
        $this->cleanup();
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getFirstEvent();
        $expected['stages'] = [
            [
                'id' => Stage::FIRST_STAGE,
                'description' => 'First stage',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/runners/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/classes/',
                ],
            ],
            [
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/runners/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/classes/',
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
            'created' => '2022-03-01T10:01:00.000+00:00',
            'modified' => '2022-03-01T10:01:00.000+00:00',
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
            'created' => '2022-03-07T10:01:00.000+00:00',
            'modified' => '2022-03-07T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00'
            ]
        ];
    }

    public function testGetData_shouldReturnRaid()
    {
        $this->cleanup();
        $this->get($this->_getEndpoint() . EventsFixture::FIRST_RAID);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getSecondEvent();
        $expected['stages'] = [
            [
                'id' => StagesFixture::STAGE_RAID,
                'description' => 'Stage raid',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3',
                    'results' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3/teams/',
                    'classes' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3/classes/',
                ],
            ],
        ];
        $expected['federation'] = [
            'id' => Federation::IOF,
            'description' => 'IOF OEVENTOR',
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    public function testGetData_notAuthenticatedAsDesktopClient()
    {
        $this->loadAuthToken('bad_fake_token');
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $this->assertException('Forbidden', 403, 'Invalid Bearer token');
    }

    public function testGetData_authenticatedAsDesktopClient()
    {
        $this->loadAuthToken(EventsController::FAKE_TOKEN);
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = $this->_getFirstEvent();
        $expected['stages'] = [
            [
                'id' => Stage::FIRST_STAGE,
                'description' => 'First stage',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/runners/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/classes/',
                ],
            ],
            [
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/runners/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/classes/',
                ],
            ],
        ];
        $expected['federation'] = [
            'id' => Federation::FEDO,
            'description' => 'FEDO SICO',
        ];
        $this->assertEquals($expected, $bodyDecoded['event']);
    }

    public function testAddNew()
    {
        $data = [
            'description' => 'Test New Race',
            'initial_date' => '2024-03-26',
            'final_date' => '2024-03-26',
            'federation_id' => null,
        ];
        $this->post($this->_getEndpoint(), $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($data['description'], $bodyDecoded['data']['description']);
        $this->assertEquals($data['initial_date'], $bodyDecoded['data']['initial_date']);
        $this->assertEquals($data['final_date'], $bodyDecoded['data']['final_date']);

        $db = EventsTable::load()->getEventFromUser($bodyDecoded['data']['id'], UsersFixture::USER_ADMIN_ID);
        $this->assertEquals($data['description'], $db->description);
    }
}
