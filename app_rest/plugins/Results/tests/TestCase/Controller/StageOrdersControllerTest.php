<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Model\Table\RankingOrganizersTable;
use Rankings\Test\Fixture\RankingOrganizersFixture;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\StageOrdersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UsersEventsFixture;

class StageOrdersControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        FederationsFixture::LOAD,
        UsersEventsFixture::LOAD,
        UsersFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        StageOrdersFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        ResultTypesFixture::LOAD,
        RunnerResultsFixture::LOAD,
        RankingOrganizersFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT
            . '/stages/' . Stage::FIRST_STAGE . '/stageOrders/';
    }

    public function testGetListReturnsOnlySelectedFields()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertCount(1, $bodyDecoded['data']);
        $row = $bodyDecoded['data'][0];
        $this->assertEquals([
            'id', 'stage_order', 'description', 'original_event_id', 'original_stage_id',
            'is_official', 'start', 'created', '_c',
        ], array_keys($row));
        $this->assertEquals('StageOrderManagement', $row['_c']);
        $this->assertEquals(StageOrdersFixture::STAGE_1, $row['id']);
        $this->assertEquals(1, $row['stage_order']);
        $this->assertEquals('Long stage', $row['description']);
        $this->assertEquals(Event::FIRST_EVENT, $row['original_event_id']);
        $this->assertEquals(Stage::FIRST_STAGE, $row['original_stage_id']);
        $this->assertFalse($row['is_official']);
        $this->assertNotEmpty($row['start']);
        $this->assertNotEmpty($row['created']);
    }

    public function testGetListForbiddenForNonOwner()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->get($this->_getEndpoint());

        $this->assertResponseCode(403);
    }

    public function testEditForbiddenForNonOwner()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->patch($this->_getEndpoint() . StageOrdersFixture::STAGE_1, ['description' => 'x']);

        $this->assertResponseCode(403);
    }

    public function testAddNewAsManagerCreatesInStage()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            '_c' => 'PostStageOrdersBody',
            'description' => 'Manual stage order',
            'stage_order' => 5,
            'start' => '2024-05-05 09:00:00',
            'is_official' => true,
            'original_event_id' => EventsFixture::FIRST_RAID,
            'original_stage_id' => StagesFixture::STAGE_RAID,
        ];
        $this->post($this->_getEndpoint(), $data);

        $json = $this->assertJsonResponseOK();
        $row = $json['data'];
        $this->assertEquals('StageOrderManagement', $row['_c']);
        $this->assertEquals('Manual stage order', $row['description']);
        $this->assertEquals(5, $row['stage_order']);
        $this->assertTrue($row['is_official']);
        $this->assertEquals(EventsFixture::FIRST_RAID, $row['original_event_id']);
        $this->assertEquals(StagesFixture::STAGE_RAID, $row['original_stage_id']);

        $db = StageOrdersTable::load()->get($row['id']);
        $this->assertEquals(Event::FIRST_EVENT, $db->event_id);
        $this->assertEquals(Stage::FIRST_STAGE, $db->stage_id);
        $this->assertNotEmpty($db->computed);
    }

    public function testAddNewForbiddenForNonManager()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $this->post($this->_getEndpoint(), ['description' => 'x', 'stage_order' => 1]);

        $this->assertResponseCode(403);
    }

    public function testEditUpdatesEditableFields()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            '_c' => 'PatchStageOrdersBody',
            'description' => 'Updated description',
            'start' => '2024-05-05 09:00:00',
            'is_official' => true,
            'original_event_id' => Event::FIRST_EVENT,
            'original_stage_id' => Stage::FIRST_STAGE,
        ];
        $this->patch($this->_getEndpoint() . StageOrdersFixture::STAGE_1, $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $row = $bodyDecoded['data'];
        $this->assertEquals([
            'id', 'stage_order', 'description', 'original_event_id', 'original_stage_id',
            'is_official', 'start', 'created', '_c',
        ], array_keys($row));
        $this->assertEquals('StageOrderManagement', $row['_c']);
        $this->assertEquals(StageOrdersFixture::STAGE_1, $row['id']);
        $this->assertEquals(1, $row['stage_order']);
        $this->assertEquals('Updated description', $row['description']);
        $this->assertTrue($row['is_official']);
        $this->assertNotEmpty($row['created']);
        $db = StageOrdersTable::load()->get(StageOrdersFixture::STAGE_1);
        $this->assertEquals('Updated description', $db->description);
        $this->assertTrue($db->is_official);
        $this->assertEquals('2024-05-05 09:00:00', $db->start->format('Y-m-d H:i:s'));
    }

    public function testEditCannotChangeOriginalIdsOnceSet()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'description' => 'Long stage',
            'original_event_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
            'original_stage_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
            'computed' => '2000-01-01 00:00:00',
        ];
        $this->patch($this->_getEndpoint() . StageOrdersFixture::STAGE_1, $data);

        $this->assertJsonResponseOK();
        $db = StageOrdersTable::load()->get(StageOrdersFixture::STAGE_1);
        $this->assertEquals(Event::FIRST_EVENT, $db->original_event_id);
        $this->assertEquals(Stage::FIRST_STAGE, $db->original_stage_id);
        $this->assertEquals('2024-01-02 10:00:05', $db->computed->format('Y-m-d H:i:s'));
    }

    public function testEditCanFillOriginalIdsWhenNull()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $endpoint = ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT
            . '/stages/' . StagesFixture::STAGE_FEDO_2 . '/stageOrders/' . StageOrdersFixture::WITHOUT_ORIGINAL_IDS;
        $data = [
            'description' => 'No links yet',
            'original_event_id' => EventsFixture::FIRST_RAID,
            'original_stage_id' => StagesFixture::STAGE_RAID,
        ];
        $this->patch($endpoint, $data);

        $this->assertJsonResponseOK();
        $db = StageOrdersTable::load()->get(StageOrdersFixture::WITHOUT_ORIGINAL_IDS);
        $this->assertEquals(EventsFixture::FIRST_RAID, $db->original_event_id);
        $this->assertEquals(StagesFixture::STAGE_RAID, $db->original_stage_id);
    }

    public function testEditIgnoresNonDescriptionFields()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'description' => 'New text',
            'stage_order' => 99, // not accessible, must be ignored
        ];
        $this->patch($this->_getEndpoint() . StageOrdersFixture::STAGE_1, $data);

        $this->assertJsonResponseOK();
        $db = StageOrdersTable::load()->get(StageOrdersFixture::STAGE_1);
        $this->assertEquals('New text', $db->description);
        $this->assertEquals(1, $db->stage_order); // unchanged
    }

    public function testEditEmptyDescriptionIsRejected()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = ['description' => ''];
        $this->patch($this->_getEndpoint() . StageOrdersFixture::STAGE_1, $data);

        $this->assertNotEquals(200, $this->_response->getStatusCode(), $this->_getBodyAsString());
        $db = StageOrdersTable::load()->get(StageOrdersFixture::STAGE_1);
        $this->assertEquals('Long stage', $db->description); // untouched
    }

    public function testDeleteRemovesStageOrderWithItsComputedResults()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $computed = $this->_saveRunnerResult(1, ResultType::PARTIAL_OVERALL, UploadTypes::TOTAL_POINTS);
        $organizer = $this->_saveRunnerResult(1, ResultType::PARTIAL_OVERALL, UploadTypes::COMPUTABLE_ORGANIZER);
        $otherStageOrder = $this->_saveRunnerResult(2, ResultType::PARTIAL_OVERALL, UploadTypes::TOTAL_POINTS);
        $notPartial = $this->_saveRunnerResult(1, ResultType::OVERALL, UploadTypes::TOTAL_POINTS);
        $rankingOrganizer = $this->_saveRankingOrganizer(StageOrdersFixture::STAGE_1);

        $this->delete($this->_getEndpoint() . StageOrdersFixture::STAGE_1);

        $this->assertResponseCode(204, $this->_getBodyAsString());
        $this->assertNull(StageOrdersTable::load()->find()->where(['id' => StageOrdersFixture::STAGE_1])->first());
        $RunnerResults = RunnerResultsTable::load();
        $this->assertNull($RunnerResults->find()->where(['id' => $computed])->first());
        $this->assertNull($RunnerResults->find()->where(['id' => $organizer])->first());
        $this->assertNotNull($RunnerResults->find()->where(['id' => $otherStageOrder])->first());
        $this->assertNotNull($RunnerResults->find()->where(['id' => $notPartial])->first());
        $this->assertNull(RankingOrganizersTable::load()->find()->where(['id' => $rankingOrganizer])->first());
    }

    public function testDeleteForgetsCachedStageOrders()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $StageOrders = StageOrdersTable::load();
        $StageOrders->deleteCache(Stage::FIRST_STAGE);
        $this->assertCount(1, $StageOrders->getAllInStage(Stage::FIRST_STAGE));

        $this->delete($this->_getEndpoint() . StageOrdersFixture::STAGE_1);

        $this->assertResponseCode(204, $this->_getBodyAsString());
        $this->assertCount(0, $StageOrders->getAllInStage(Stage::FIRST_STAGE));
    }

    public function testDeleteForbiddenForNonOwner()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);
        $computed = $this->_saveRunnerResult(1, ResultType::PARTIAL_OVERALL, UploadTypes::TOTAL_POINTS);

        $this->delete($this->_getEndpoint() . StageOrdersFixture::STAGE_1);

        $this->assertResponseCode(403);
        $this->assertNotNull(StageOrdersTable::load()->find()->where(['id' => StageOrdersFixture::STAGE_1])->first());
        $this->assertNotNull(RunnerResultsTable::load()->find()->where(['id' => $computed])->first());
    }

    public function testDeleteStageOrderFromAnotherStageIsNotFound()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $endpoint = ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT
            . '/stages/' . StagesFixture::STAGE_FEDO_2 . '/stageOrders/' . StageOrdersFixture::STAGE_1;

        $this->delete($endpoint);

        $this->assertResponseCode(404);
        $this->assertNotNull(StageOrdersTable::load()->find()->where(['id' => StageOrdersFixture::STAGE_1])->first());
    }

    private function _saveRunnerResult(int $stageOrder, string $resultTypeId, string $uploadType): string
    {
        $RunnerResults = RunnerResultsTable::load();
        $result = $RunnerResults->fillNewWithStage([], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $result->runner_id = Runner::FIRST_RUNNER;
        $result->class_id = ClassEntity::ME;
        $result->stage_order = $stageOrder;
        $result->result_type_id = $resultTypeId;
        $result->upload_type = $uploadType;
        return $RunnerResults->saveOrFail($result)->id;
    }

    private function _saveRankingOrganizer(string $stageOrderId): string
    {
        $RankingOrganizers = RankingOrganizersTable::load();
        $organizer = $RankingOrganizers->fillNewWithUuid([]);
        $organizer->first_name = 'Org';
        $organizer->last_name = 'Anizer';
        $organizer->stage_order_id = $stageOrderId;
        return $RankingOrganizers->saveOrFail($organizer)->id;
    }

    public function testEditStageOrderFromAnotherStageIsNotFound()
    {
        $this->skipNextRequestInSwagger();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $endpoint = ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT
            . '/stages/' . StagesFixture::STAGE_FEDO_2 . '/stageOrders/' . StageOrdersFixture::STAGE_1;
        $this->patch($endpoint, ['description' => 'x']);

        $this->assertNotEquals(200, $this->_response->getStatusCode(), $this->_getBodyAsString());
    }
}
