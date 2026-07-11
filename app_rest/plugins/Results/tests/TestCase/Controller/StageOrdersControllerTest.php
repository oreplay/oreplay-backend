<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\StageOrdersTable;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
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
