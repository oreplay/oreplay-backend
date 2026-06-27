<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Cache\Cache;
use Rankings\Lib\ScoringAlgorithms\SimpleScoreCalculator;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\StagesFixture;

class RankingSettingsControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        RankingsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/';
    }

    private function _validCreateData(): array
    {
        return [
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => EventsFixture::EVENT_TOMORROW_RANKING,
            'stage_id' => StagesFixture::STAGE_RANKING,
            'max_points' => 100,
            'round_precision' => 0,
        ];
    }

    private function _insertRankingCreatedAt(string $id, string $created): void
    {
        $Rankings = RankingsTable::load();
        $ranking = $Rankings->newEmptyEntity();
        $ranking->id = $id;
        $ranking = $Rankings->patchEntity($ranking, $this->_validCreateData());
        $Rankings->saveOrFail($ranking);
        $Rankings->updateAll(['created' => $created], ['id' => $id]);
    }

    public function testGetListReturnsRankings()
    {
        $this->get($this->_getEndpoint());

        $json = $this->assertJsonResponseOK();
        $this->assertEquals(1, $json['total']);
        $this->assertEquals(10, $json['limit']);
        $this->assertArrayHasKey('_links', $json);
        $this->assertArrayHasKey('self', $json['_links']);
        $this->assertCount(1, $json['data']);
        $this->assertEquals(RankingsTable::FIRST_RANKING, $json['data'][0]['id']);
        $this->assertArrayHasKey('_links', $json['data'][0]);
        $this->assertArrayHasKey('self', $json['data'][0]['_links']);
        $res = 'http://dev.example.com/competitions/85a7c518-54a8-4180-9708-9dcd4e9906c7/e662499c-4d66-4675-8501-905bcfe28a41';
        $this->assertEquals($res, $json['data'][0]['_links']['results']['href']);
    }

    public function testGetListIsPaginatedAndOrderedByCreatedDesc()
    {
        // the fixture ranking 'regional100pts' is created 2024-01-02
        $this->_insertRankingCreatedAt('oldest2020', '2020-01-01 00:00:00');
        $this->_insertRankingCreatedAt('newest2030', '2030-01-01 00:00:00');

        $this->get($this->_getEndpoint() . '?limit=2&page=1');

        $json = $this->assertJsonResponseOK();
        $this->assertEquals(3, $json['total']);
        $this->assertEquals(2, $json['limit']);
        $this->assertCount(2, $json['data']);
        // newest first, oldest paged out
        $this->assertEquals('newest2030', $json['data'][0]['id']);
        $this->assertEquals(RankingsTable::FIRST_RANKING, $json['data'][1]['id']);
    }

    public function testGetDataReturnsOneRanking()
    {
        $this->get($this->_getEndpoint() . RankingsTable::FIRST_RANKING);

        $json = $this->assertJsonResponseOK();
        $this->assertEquals(RankingsTable::FIRST_RANKING, $json['data']['id']);
        $this->assertEquals(100, $json['data']['max_points']);
        $this->assertEquals('Regional ranking 100pts', $json['data']['title']);

        $links = $json['data']['_links'];
        $this->assertStringEndsWith('/api/v1/rankings/' . RankingsTable::FIRST_RANKING, $links['self']['href']);
        $this->assertStringEndsWith(
            '/competitions/' . EventsFixture::EVENT_TOMORROW_RANKING
                . '/' . StagesFixture::STAGE_RANKING,
            $links['results']['href']
        );
    }

    public function testGetDataNotFoundReturns404()
    {
        $this->get($this->_getEndpoint() . 'doesNotExist');

        $this->assertException('Not Found', 404);
    }

    public function testAddNewGeneratesUuidServerSide()
    {
        $data = $this->_validCreateData();
        $data['title'] = 'My new ranking';
        $data['nc_true'] = 5;
        $data['nc_false'] = 10;
        $data['status_scores'] = '[null,1,2,3,4,5]';
        $data['excluded_class_names'] = '["ELITE"]';
        $data['overall_settings'] =
            '{"totalCircuitRaces":3,"maxRacesCounted":2,"organizerScoringFraction":0.5,"minPointsAsOrg":20}';
        $this->post($this->_getEndpoint(), $data);

        $json = $this->assertJsonResponseOK();
        $newId = $json['data']['id'];
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $newId);
        $this->assertEquals(SimpleScoreCalculator::class, $json['data']['scoring_algorithm']);
        $this->assertEquals('My new ranking', $json['data']['title']);

        $saved = RankingsTable::load()->get($newId);
        $this->assertEquals('My new ranking', $saved->title);
        $this->assertEquals(100, $saved->max_points);
        $this->assertEquals(5, $saved->nc_true);
        $this->assertEquals(10, $saved->nc_false);
        $this->assertEquals('[null,1,2,3,4,5]', $saved->status_scores);
        $this->assertEquals('["ELITE"]', $saved->excluded_class_names);
        $this->assertEquals(
            '{"totalCircuitRaces":3,"maxRacesCounted":2,"organizerScoringFraction":0.5,"minPointsAsOrg":20}',
            $saved->overall_settings
        );
    }

    public function testAddNewRejectsClientProvidedNonUuidId()
    {
        $data = $this->_validCreateData();
        $data['id'] = 'regional100pts';
        $this->post($this->_getEndpoint(), $data);

        $this->assertExceptionMessage('ID must be in UUID format ISO 9834 or not provided', 400);
    }

    public function testAddNewMissingRequiredFieldReturnsValidationError()
    {
        $this->post($this->_getEndpoint(), ['max_points' => 100]);

        $this->assertException('Validation error', 400);
    }

    public function testEditUpdatesRankingAndInvalidatesCache()
    {
        $Rankings = RankingsTable::load();
        $Rankings->getCached(RankingsTable::FIRST_RANKING);
        $Rankings->getCachedByStage(StagesFixture::STAGE_RANKING);
        $byId = '_getRankingSettings_' . RankingsTable::FIRST_RANKING;
        $byStage = '_getRankingSettingsByStage_' . StagesFixture::STAGE_RANKING;
        $this->assertNotEmpty(Cache::read($byId));
        $this->assertNotEmpty(Cache::read($byStage));

        // full example of every editable property, so the generated PatchRankingSettingsBody type is
        // complete; every value differs from the fixture so the asserts prove each field is editable
        $data = [
            '_c' => 'PatchRankingSettingsBody',
            'scoring_algorithm' => SimpleScoreCalculator::class,
            'event_id' => EventsFixture::EVENT_TOMORROW_RANKING,
            'stage_id' => StagesFixture::STAGE_RANKING,
            'title' => 'My circuit title',
            'max_points' => 250,
            'round_precision' => 2,
            'nc_true' => 5,
            'nc_false' => 10,
            'status_scores' => '[null,1,2,3,4,5]',
            'excluded_class_names' => '["ELITE"]',
            'overall_settings' => '{"totalCircuitRaces":3,"maxRacesCounted":2,"organizerScoringFraction":0.5,"minPointsAsOrg":20}',
        ];
        $this->patch($this->_getEndpoint() . RankingsTable::FIRST_RANKING, $data);

        $json = $this->assertJsonResponseOK();
        $this->assertEquals(250, $json['data']['max_points']);
        $this->assertEquals('My circuit title', $json['data']['title']);
        $this->assertEmpty(Cache::read($byId));
        $this->assertEmpty(Cache::read($byStage));

        $saved = RankingsTable::load()->get(RankingsTable::FIRST_RANKING);
        $this->assertEquals('My circuit title', $saved->title);
        $this->assertEquals(250, $saved->max_points);
        $this->assertEquals(2, $saved->round_precision);
        $this->assertEquals(5, $saved->nc_true);
        $this->assertEquals(10, $saved->nc_false);
        $this->assertEquals('[null,1,2,3,4,5]', $saved->status_scores);
        $this->assertEquals('["ELITE"]', $saved->excluded_class_names);
        $this->assertEquals(
            '{"totalCircuitRaces":3,"maxRacesCounted":2,"organizerScoringFraction":0.5,"minPointsAsOrg":20}',
            $saved->overall_settings
        );
    }

    public function testEditIgnoresIdChange()
    {
        $data = [
            '_c' => 'PatchRankingSettingsBody',
            'id' => 'hackedId',
            'max_points' => 5,
        ];
        $this->patch($this->_getEndpoint() . RankingsTable::FIRST_RANKING, $data);

        $json = $this->assertJsonResponseOK();
        // the id is untouched while the other field is updated
        $this->assertEquals(RankingsTable::FIRST_RANKING, $json['data']['id']);
        $this->assertEquals(5, $json['data']['max_points']);
        // the attempted new id was never created
        $this->assertNull(RankingsTable::load()->find()->where(['id' => 'hackedId'])->first());
    }

    public function testDeleteSoftDeletesRanking()
    {
        $this->delete($this->_getEndpoint() . RankingsTable::FIRST_RANKING);
        $this->assertResponse204NoContent();

        $Rankings = RankingsTable::load();
        // soft-deleted rows are excluded from normal reads
        $this->assertNull($Rankings->find()->where(['id' => RankingsTable::FIRST_RANKING])->first());
        // but the row still exists with the deleted timestamp set
        $deleted = $Rankings->find()->where(['id' => RankingsTable::FIRST_RANKING])->withDeleted(true)->first();
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted);
    }

    public function testNonManagerIsForbidden()
    {
        $this->clearUserCache();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);

        $this->get($this->_getEndpoint());

        $this->assertException('Forbidden', 403);
    }
}
