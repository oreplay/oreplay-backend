<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ResultType;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StageOrdersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\ResultTypesFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\StagesFixture;

class RankingRunnerManagementControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
        ResultTypesFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        RankingsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . RankingsTable::FIRST_RANKING . '/events/' . EventsFixture::EVENT_TOMORROW_RANKING
            . '/stages/' . StagesFixture::STAGE_RANKING . '/runnerResults/';
    }

    public function testAddNew()
    {
        StageOrdersTable::load()->getAllCreatingOne(
            StagesFixture::STAGE_RANKING,
            EventsFixture::EVENT_TOMORROW_RANKING,
            StagesFixture::STAGE_RANKING
        );
        $RunnersTable = RunnersTable::load();
        $runner = $RunnersTable->fillNewWithStage(
            [],
            EventsFixture::EVENT_TOMORROW_RANKING,
            StagesFixture::STAGE_RANKING,
        );
        $runner->class_id = ClassEntity::ME;
        $RunnersTable->saveOrFail($runner);

        $params = [
            'upload_type' => UploadTypes::COMPUTABLE_ORGANIZER,
            'runner_id' => $runner->id,
            'stage_order' => 1,
        ];
        $this->post($this->_getEndpoint(), $params);

        $json = $this->assertJsonResponseOK();
        $this->assertEquals(UploadTypes::COMPUTABLE_ORGANIZER, $json['data']['upload_type']);
        $this->assertEquals(StatusCode::OK, $json['data']['status_code']);
        $this->assertEquals(ResultType::PARTIAL_OVERALL, $json['data']['result_type_id']);
    }
}
