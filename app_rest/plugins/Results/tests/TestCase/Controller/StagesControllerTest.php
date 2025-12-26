<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\UploadLogsTable;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\FederationsFixture;
use Results\Test\Fixture\RawUploadsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\StageTypesFixture;
use Results\Test\Fixture\UploadLogsFixture;
use Results\Test\Fixture\UsersEventsFixture;

class StagesControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        FederationsFixture::LOAD,
        UsersEventsFixture::LOAD,
        UsersFixture::LOAD,
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        StageTypesFixture::LOAD,
        ClubsFixture::LOAD,
        RunnersFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UploadLogsFixture::LOAD,
        RawUploadsFixture::LOAD,
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
                '_c' => Stage::class,
                'id' => Stage::FIRST_STAGE,
                'description' => 'First stage',
                'last_logs' => [],
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/results/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/51d63e99-5d7c-4382-a541-8567015d8eed/classes/',
                ],
            ],
            [
                '_c' => Stage::class,
                'id' => StagesFixture::STAGE_FEDO_2,
                'description' => 'Second stage',
                'last_logs' => [],
                '_links' => [
                    'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                    'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/results/',
                    'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/classes/',
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
            '_c' => Stage::class,
            'id' => StagesFixture::STAGE_FEDO_2,
            'description' => 'Second stage',
            'stage_type' => [
                '_c' => StageType::class,
                'id' => StageType::CLASSIC,
                'description' => 'Foot-O, MTBO, Ski-O',
            ],
            'last_logs' => [],
            '_links' => [
                'self' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964',
                'results' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/results/',
                'classes' => 'http://dev.example.com/api/v1/events/8f3b542c-23b9-4790-a113-b83d476c0ad9/stages/8f45d409-72bc-4cdc-96e9-0a2c4504d964/classes/',
            ],
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    public function testAddNew()
    {
        $description = 'My new test stage';
        $data = [
            'description' => $description,
        ];
        $this->post($this->_getEndpoint(), $data);

        $bodyDecoded = $this->assertJsonResponseOK()['data'];
        $this->assertEquals($description, $bodyDecoded['description']);
        $this->assertEquals(StageType::CLASSIC, $bodyDecoded['stage_type']['id']);
    }

    public function testAddNew_includingOptionalStageType()
    {
        $description = 'My other test stage';
        $data = [
            'description' => $description,
            'stage_type_id' => StageType::MASS_START,
        ];
        $this->post($this->_getEndpoint(), $data);

        $bodyDecoded = $this->assertJsonResponseOK()['data'];
        $this->assertEquals($description, $bodyDecoded['description']);
        $this->assertEquals(StageType::MASS_START, $bodyDecoded['stage_type']['id']);
    }

    public function testEdit()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'description' => 'Some stage',
            'stage_type_id' => StageType::SCORE,
        ];
        $this->patch($this->_getEndpoint() . Stage::FIRST_STAGE, $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($data['description'], $bodyDecoded['data']['description']);
        /** @var Stage $db */
        $db = StagesTable::load()->get(Stage::FIRST_STAGE);
        $this->assertEquals($data['description'], $db->description);
        $this->assertEquals($data['stage_type_id'], $db->stage_type_id);
    }

    public function testEdit_shouldSetStateEnded()
    {
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
        $data = [
            'state_end' => true,
        ];
        $this->patch($this->_getEndpoint() . Stage::FIRST_STAGE, $data);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals('First stage', $bodyDecoded['data']['description']);

        $logs = UploadLogsTable::load()->find()
            ->where(['stage_id' => Stage::FIRST_STAGE, 'state' => UploadLog::STATE_ENDED])->all()->count();
        $this->assertEquals(1, $logs);
    }

    public function testDelete_withCleanParamShouldNotRemoveStageButEmptyContents()
    {
        $this->delete($this->_getEndpoint() . Stage::FIRST_STAGE . '?clean=1');

        $this->assertEquals(204, $this->_response->getStatusCode(), $this->_getBodyAsString());
        $stage = StagesTable::load()->findById(Stage::FIRST_STAGE)->first();
        $this->assertEquals(Stage::FIRST_STAGE, $stage->id);
        $class = ClassesTable::load()->findById(Stage::FIRST_STAGE)->first();
        $this->assertNull($class);
    }

    public function testDelete_withoutCleanParamShouldRemoveStage()
    {
        $this->delete($this->_getEndpoint() . Stage::FIRST_STAGE . '');

        $this->assertEquals(204, $this->_response->getStatusCode(), $this->_getBodyAsString());
        $stage = StagesTable::load()->findById(Stage::FIRST_STAGE)->first();
        $this->assertNull($stage);
        $class = ClassesTable::load()->findById(Stage::FIRST_STAGE)->first();
        $this->assertNull($class);
    }
}
