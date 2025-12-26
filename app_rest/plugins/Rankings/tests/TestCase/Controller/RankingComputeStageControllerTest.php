<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;

class RankingComputeStageControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        //SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
        //TeamsFixture::LOAD,
        //TeamResultsFixture::LOAD,
        ResultTypesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        RankingsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . RankingsTable::FIRST_RANKING . '/events/' . Event::FIRST_EVENT
            . '/stages/' . Stage::FIRST_STAGE . '/compute/';
    }

    public function testAddNew()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $params = [
            'classes' => 'all'
        ];
        $this->post($this->_getEndpoint(), $params);

        $body = $this->_getBodyAsString();
        $this->assertResponseFailure($body);

        $bodyDecoded = json_decode($body, true);
        $string = 'Ranking compute errors: [{"error":"';
        if (str_contains($bodyDecoded['message'], 'failed during DNS lookup')) {
            $this->markTestSkipped($bodyDecoded['message']);
        }
        $this->assertStringStartsWith($string, $bodyDecoded['message']);
    }
}
