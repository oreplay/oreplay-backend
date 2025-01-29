<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use DateTime;
use Results\Model\Entity\Event;
use Results\Model\Entity\Federation;
use Results\Model\Entity\Organizer;
use Results\Model\Entity\Stage;
use Results\Model\Table\EventsTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\OrganizersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\TokensFixture;
use Results\Test\Fixture\UsersEventsFixture;

class EventsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        FederationsFixture::LOAD,
        OrganizersFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        UsersEventsFixture::LOAD,
        TokensFixture::LOAD,
        StageTypesFixture::LOAD,
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
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][2]);
        $this->assertEquals($this->_getFirstEvent(), $bodyDecoded['data'][3]);
    }

    public function testGetList_shouldFilterByUser()
    {
        $this->get($this->_getEndpoint() . '?user_id=' . UsersFixture::USER_ADMIN_ID);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstEvent(), $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_NoParams()
    {
        $this->get($this->_getEndpoint() . '?page=3&limit=1');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $this->assertEquals($this->_getSecondEvent(), $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_WhenToday()
    {
        $today = (new DateTime('now'))->format('Y-m-d');
        $this->get($this->_getEndpoint() . '?when=today&show_hidden=1&initial_date:lte='
            . $today . '&final_date:gte=' . $today);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'id' => EventsFixture::EVENT_TODAY,
            'description' => 'Today event',
            'initial_date' => $today,
            'final_date' => $today,
            'federation_id' => 'IOF',
            'picture' => null,
            'website' => null,
            'scope' => null,
            'location' => null,
            'country_code' => null,
            'is_hidden' => false,
            'created' => '2022-03-10T10:01:00.000+00:00',
            'modified' => '2022-03-10T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/' . EventsFixture::EVENT_TODAY
            ],
            'organizer' => $this->_organizer(),
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
            'picture' => null,
            'website' => null,
            'scope' => null,
            'location' => null,
            'country_code' => null,
            'is_hidden' => false,
            'created' => '2022-03-13T10:01:00.000+00:00',
            'modified' => '2022-03-13T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8b2-tomorrow'
            ],
            'organizer' => $this->_organizer(),
        ];
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    public function testGetList_paginated_WhenPast()
    {
        $this->get($this->_getEndpoint() . '?when=past&page=1&limit=1');

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
                'stage_type' => [
                    'id' => '29d5050b-4769-4be5-ace4-7e5973f68e3c',
                    'description' => 'Foot-O, MTBO, Ski-O',
                ],
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/runners/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/classes/',
                ],
            ],
            [
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
                'stage_type' => [
                    'id' => '29d5050b-4769-4be5-ace4-7e5973f68e3c',
                    'description' => 'Foot-O, MTBO, Ski-O',
                ],
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
        $expected['organizer'] = $this->_organizer();
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    private function _organizer(): array
    {
        return [
            'id' => Organizer::ID,
            'name' => Organizer::NAME,
            'country' => 'ES',
            'region' => 'ES-VC',
        ];
    }

    private function _getFirstEvent(): array
    {
        return [
            'id' => Event::FIRST_EVENT,
            'description' => 'Test Foot-o',
            'initial_date' => '2024-01-25',
            'final_date' => '2024-01-25',
            'federation_id' => Federation::FEDO,
            'picture' => null,
            'website' => null,
            'scope' => null,
            'location' => null,
            'country_code' => null,
            'is_hidden' => false,
            'created' => '2022-03-01T10:01:00.000+00:00',
            'modified' => '2022-03-01T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9'
            ],
            'organizer' => $this->_organizer(),
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
            'picture' => null,
            'website' => null,
            'scope' => null,
            'location' => null,
            'country_code' => null,
            'is_hidden' => false,
            'created' => '2022-03-07T10:01:00.000+00:00',
            'modified' => '2022-03-07T10:01:00.000+00:00',
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00'
            ],
            'organizer' => $this->_organizer(),
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
                'stage_type' => [
                    'id' => 'a30b2db1-5649-491a-b5a8-ca53e4e58461',
                    'description' => 'Raid',
                ],
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3',
                    'results' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3/results/',
                    'classes' => 'http://dev.example.com/api/v1/events/1b10cfcc-b3f2-40bb-8dbe-8cb5d8b24c00/stages/91c54cd6-98de-441c-a71c-cda466c1abc3/classes/',
                ],
            ],
        ];
        $expected['federation'] = [
            'id' => Federation::IOF,
            'description' => 'IOF OEVENTOR',
        ];
        $expected['organizer'] = $this->_organizer();
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    public function testGetData_authenticatedAsDesktopClient()
    {
        $this->loadAuthToken(TokensFixture::FIRST_TOKEN);
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            'id' => Event::FIRST_EVENT,
            'description' => 'Test Foot-o',
        ];
        $expected['stages'] = [
            [
                'id' => Stage::FIRST_STAGE,
                'description' => 'First stage',
            ],
            [
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
            ],
        ];
        $this->assertEquals($expected, $bodyDecoded['event']);
    }

    public function testGetData_authenticatedAsUser()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(Event::FIRST_EVENT, $bodyDecoded['data']['id']);
    }

    public function testGetData_notAuthenticatedAsDesktopClient()
    {
        $this->loadAuthToken('bad_fake_token');
        $this->get($this->_getEndpoint() . Event::FIRST_EVENT);

        $this->assertException('Unauthorized', 401, 'Verify authorization error: The access token provided is invalid');
    }

    public function testAddNew()
    {
        $data = [
            'id' => '7f83e207-5a6e-456e-a08f-935eef54eca2',
            'description' => 'Test New Race',
            'initial_date' => '2024-03-26',
            'final_date' => '2024-03-26',
            'federation_id' => Federation::FEDO,
        ];
        $this->post($this->_getEndpoint(), $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($data['description'], $bodyDecoded['data']['description']);
        $this->assertEquals($data['initial_date'], $bodyDecoded['data']['initial_date']);
        $this->assertEquals($data['final_date'], $bodyDecoded['data']['final_date']);

        /** @var Event $db */
        $db = EventsTable::load()->getEventFromUser($bodyDecoded['data']['id'], UsersFixture::USER_ADMIN_ID);
        $this->assertEquals($data['description'], $db->description);
        $this->assertEquals($data['federation_id'], $db->federation_id);
        $this->assertEquals($data['id'], $db->id);
    }

    public function testAddNew_shouldNotAddFinalDateBefaoreInitialDate()
    {
        $data = [
            'id' => '7f83e207-5a6e-456e-a08f-935eef54eca1',
            'description' => 'Test New Race',
            'initial_date' => '2024-03-26',
            'final_date' => '2024-03-25',
            'federation_id' => Federation::FEDO,
        ];
        $this->post($this->_getEndpoint(), $data);

        $expectedError = [
            'final_date' => [
                'checkDates' => 'The final date cannot be earlier than the initial date.',
            ]
        ];
        $this->assertException('Validation error', 400, 'Validation Exception');
        $this->assertValidationErrorMessage($expectedError);
    }

    public function testEdit()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'is_hidden' => true,
            'description' => 'Some description',
            'scope' => 'local',
            'location' => 'somewhere',
            'country_code' => 'ES',
            'website' => 'https://www.oreplay.es',
            'picture' => 'https://www.oreplay.es/logo.svg',
            'initial_date' => '2024-06-10',
            'final_date' => '2024-06-10',
        ];
        $this->patch($this->_getEndpoint() . Event::FIRST_EVENT, $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($data['is_hidden'], $bodyDecoded['data']['is_hidden']);
        $this->assertEquals($data['description'], $bodyDecoded['data']['description']);
        $this->assertEquals($data['scope'], $bodyDecoded['data']['scope']);
        $this->assertEquals($data['location'], $bodyDecoded['data']['location']);
        $this->assertEquals($data['country_code'], $bodyDecoded['data']['country_code']);
        $this->assertEquals($data['website'], $bodyDecoded['data']['website']);
        $this->assertEquals($data['picture'], $bodyDecoded['data']['picture']);
        $this->assertEquals($data['initial_date'], $bodyDecoded['data']['initial_date']);
        $this->assertEquals($data['final_date'], $bodyDecoded['data']['final_date']);
    }

    public function testEdit_shouldNotEditWithInvalidToken()
    {
        $this->loadAuthToken('bad_token');
        $data = [
            'description' => 'Some description',
        ];
        $this->patch($this->_getEndpoint() . Event::FIRST_EVENT, $data);

        $this->assertResponseCode(401);
    }

    public function testEdit_shouldNotEditFromAnotherUser()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'description' => 'Some description',
        ];
        $this->patch($this->_getEndpoint() . EventsFixture::EVENT_TODAY, $data);

        $this->assertResponseCode(403);
    }

    public function testEdit_shouldNotEditFinalDateBefaoreInitialDate()
    {
        $data = [
            'initial_date' => '2024-03-26',
            'final_date' => '2024-03-25',
        ];
        $this->patch($this->_getEndpoint() . Event::FIRST_EVENT, $data);

        $this->assertResponseCode(400);
    }

    public function testDelete()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->delete($this->_getEndpoint() . Event::FIRST_EVENT);
        $this->assertResponse204NoContent();
        $this->assertNull(EventsTable::load()->findById(Event::FIRST_EVENT)->first());
    }

    public function testDelete_shouldNotDeleteWithInvalidToken()
    {
        $this->loadAuthToken('bad_token');
        $this->delete($this->_getEndpoint() . Event::FIRST_EVENT);
        $this->assertResponseCode(401);
        $this->assertNotNull(EventsTable::load()->findById(Event::FIRST_EVENT)->first());
    }

    public function testDelete_shouldNotDeleteFromAnotherUser()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->delete($this->_getEndpoint() . EventsFixture::EVENT_TODAY);
        $this->assertResponseCode(403);
    }
}
