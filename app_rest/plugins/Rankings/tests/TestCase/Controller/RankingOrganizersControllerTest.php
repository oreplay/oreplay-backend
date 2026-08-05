<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Entity\Ranking;
use Rankings\Model\Table\RankingOrganizersTable;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UsersEventsFixture;

class RankingOrganizersControllerTest extends ApiCommonErrorsTest
{
    private const RANKING_ID = 'a1b2c3d4-0000-4000-8000-0000000000a1';

    protected array $fixtures = [
        FederationsFixture::LOAD,
        UsersEventsFixture::LOAD,
        UsersFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        StageOrdersFixture::LOAD,
        RunnersFixture::LOAD,
        RankingsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();
        // A ranking for the first event/stage, linked to the stage order under test.
        $Rankings = RankingsTable::load();
        $Rankings->deleteCache(self::RANKING_ID);
        $ranking = $Rankings->patchFromNewWithUuid([
            'id' => self::RANKING_ID,
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'max_points' => 100,
            'round_precision' => Ranking::USE_FLOOR_INSTEAD_OF_ROUND,
        ]);
        $Rankings->saveOrFail($ranking);
    }

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . self::RANKING_ID
            . '/events/' . Event::FIRST_EVENT
            . '/stages/' . Stage::FIRST_STAGE
            . '/stageOrders/' . StageOrdersFixture::STAGE_1 . '/organizers/';
    }

    public function testAddNewCreatesOrganizer()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'description' => 'Course setter',
        ]);

        $json = $this->assertJsonResponseOK();
        $this->assertEquals('Ada', $json['data']['first_name']);
        $this->assertEquals('Lovelace', $json['data']['last_name']);
        $this->assertEquals('Course setter', $json['data']['description']);

        $db = RankingOrganizersTable::load()->get($json['data']['id']);
        $this->assertEquals(StageOrdersFixture::STAGE_1, $db->stage_order_id);
        $this->assertEquals('Course setter', $db->description);
        $this->assertNull($db->runner_id);
    }

    public function testAddNewWithExistingRunnerId()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'runner_id' => Runner::FIRST_RUNNER,
        ]);

        $json = $this->assertJsonResponseOK();
        $db = RankingOrganizersTable::load()->get($json['data']['id']);
        $this->assertEquals(Runner::FIRST_RUNNER, $db->runner_id);
    }

    public function testAddNewWithUnknownRunnerIdIsRejected()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), [
            'first_name' => 'No',
            'last_name' => 'Runner',
            'runner_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
        ]);

        $this->assertNotEquals(200, $this->_response->getStatusCode(), $this->_getBodyAsString());
    }

    public function testAddNewRejectedWhenRankingDoesNotMatchEvent()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $endpoint = ApiController::ROUTE_PREFIX . '/rankings/' . RankingsTable::FIRST_RANKING
            . '/events/' . Event::FIRST_EVENT
            . '/stages/' . Stage::FIRST_STAGE
            . '/stageOrders/' . StageOrdersFixture::STAGE_1 . '/organizers/';
        $this->post($endpoint, ['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertResponseCode(403);
    }

    public function testAddNewRejectedWhenOverOrganizerLimit()
    {
        for ($i = 0; $i <= 100; $i++) {
            $this->_seedOrganizer('Org' . $i, 'Anizer');
        }
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), ['first_name' => 'One', 'last_name' => 'TooMany']);

        $this->assertResponseCode(400);
    }

    public function testGetListReturnsOrganizers()
    {
        $this->_seedOrganizer('Ada', 'Lovelace');
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);

        $this->get($this->_getEndpoint());
        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Ada', $json['data'][0]['first_name']);
    }

    public function testGetListExcludesDeleted()
    {
        $this->_seedOrganizer('Ada', 'Lovelace');
        $deletedId = $this->_seedOrganizer('Gone', 'Runner');
        RankingOrganizersTable::load()->softDelete($deletedId);
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);

        $this->get($this->_getEndpoint());
        $json = $this->assertJsonResponseOK();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Ada', $json['data'][0]['first_name']);
    }

    public function testDeleteSoftDeletesOrganizer()
    {
        $id = $this->_seedOrganizer('Ada', 'Lovelace');
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);

        $this->delete($this->_getEndpoint() . $id);
        $this->assertResponseCode(204);

        $found = RankingOrganizersTable::load()->find()->where(['id' => $id])->first();
        $this->assertNull($found);
    }

    public function testGetListForbiddenForNonOwner()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->get($this->_getEndpoint());

        $this->assertResponseCode(403);
    }

    public function testAddNewForbiddenForNonOwner()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), ['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertResponseCode(403);
    }

    private function _seedOrganizer(string $firstName, string $lastName): string
    {
        $table = RankingOrganizersTable::load();
        $organizer = $table->patchFromNewWithUuid([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'stage_order_id' => StageOrdersFixture::STAGE_1,
        ]);
        $organizer->stage_order_id = StageOrdersFixture::STAGE_1;
        $organizer->setDirty('stage_order_id');
        $table->saveOrFail($organizer);
        return $organizer->id;
    }
}
