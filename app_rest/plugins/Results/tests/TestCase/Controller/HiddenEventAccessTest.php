<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\EventsTable;
use Results\Model\Table\TokensTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UsersEventsFixture;

class HiddenEventAccessTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        ClassesFixture::LOAD,
        ClubsFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        UsersFixture::LOAD,
        UsersEventsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return $this->_stagePath() . 'classes/';
    }

    private function _stagePath(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/' . Stage::FIRST_STAGE . '/';
    }

    /**
     * @return string[] public endpoints that serve the data of an event
     */
    private function _eventDataEndpoints(): array
    {
        return [
            ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/',
            $this->_stagePath() . 'classes/',
            $this->_stagePath() . 'clubs/',
            $this->_stagePath() . 'results/',
            $this->_stagePath() . 'resultsByClass/',
            $this->_stagePath() . 'stats/',
        ];
    }

    private function _hideTheEvent(): void
    {
        EventsTable::load()->updateAll(['is_hidden' => true], ['id' => Event::FIRST_EVENT]);
    }

    private function _sendNoToken(): void
    {
        $this->_request['headers'] = ['Accept' => 'application/json'];
    }

    public function testAnonymousCannotReadAHiddenEventsData()
    {
        $this->_hideTheEvent();
        foreach ($this->_eventDataEndpoints() as $endpoint) {
            $this->skipNextRequestInSwagger();
            $this->_sendNoToken();
            $this->get($endpoint);
            $this->assertContains($this->_response->getStatusCode(), [401, 403], $endpoint . ' ' . $this->_getBodyAsString());
        }
    }

    public function testAnotherUserCannotReadAHiddenEventsData()
    {
        $this->_hideTheEvent();
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->get($this->_stagePath() . 'results/');

        $this->assertResponseCode(403);
    }

    public function testTheOrganizerReadsTheirHiddenEventsData()
    {
        $this->_hideTheEvent();
        foreach ($this->_eventDataEndpoints() as $endpoint) {
            $this->skipNextRequestInSwagger();
            $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
            $this->get($endpoint);
            $this->assertResponseCode(200, $endpoint . ' ' . $this->_getBodyAsString());
        }
    }

    public function testTheEventsDesktopTokenReadsItsHiddenEventsData()
    {
        $this->_hideTheEvent();
        $eventToken = TokensTable::load()->createTokenForEvent(Event::FIRST_EVENT, [
            'expires' => new FrozenTime('+1 day'),
        ]);
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken($eventToken->token);
        $this->get($this->_stagePath() . 'results/');

        $this->assertResponseCode(200, $this->_getBodyAsString());
    }

    public function testAnonymousStillReadsAPublicEventsData()
    {
        foreach ($this->_eventDataEndpoints() as $endpoint) {
            $this->skipNextRequestInSwagger();
            $this->_sendNoToken();
            $this->get($endpoint);
            $this->assertResponseCode(200, $endpoint . ' ' . $this->_getBodyAsString());
        }
    }
}
