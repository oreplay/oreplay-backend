<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Rankings\Controller\RankingComputeClassController;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Model\Table\RankingsTable;
use Rankings\Test\Fixture\RankingsFixture;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\ClassEntity;
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
use Results\Test\Fixture\StageOrdersFixture;
use Results\Test\Fixture\StagesFixture;

class RankingComputeClassControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
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
        RankingsFixture::LOAD,
        StageOrdersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/rankings/' . RankingsTable::FIRST_RANKING . '/events/' . Event::FIRST_EVENT
            . '/stages/' . Stage::FIRST_STAGE . '/classes/' . ClassEntity::ME . '/compute/';
    }

    public function testAddNew()
    {
        $params = [
            'secret' => RankingComputeClassController::getSecret(),
        ];
        $this->post($this->_getEndpoint(), $params);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals('M Elite', $bodyDecoded['data']['long_name']);
        $this->assertEquals(1, count($bodyDecoded['data']['runners']));
        $overalls = $bodyDecoded['data']['runners'][0]['overalls'];
        $expectedOveralls = [
            'parts' =>[
                [
                    'id' => $overalls['parts'][0]['id'] ?? 'undefined_parts.0.id',
                    'stage_order' => 1,
                    'stage' => [
                        'id' => $overalls['parts'][0]['stage']['id'] ?? 'undefined_parts.0.stage.id',
                        'description' => 'Test Foot-o',
                    ],
                    'position' => 1,
                    'time_seconds' => null,
                    'time_behind' => null,
                    'points_final' => 100,
                    'upload_type' => UploadTypes::TOTAL_POINTS,
                    'note' => null,
                    'status_code' => '0',
                    'is_nc' => null,
                    'contributory' => null,
                ],
            ],
            'overall' => [
                'id' => $overalls['overall']['id'] ?? 'undefined_overall.id',
                'stage_order' => 1,
                'stage' => null,
                'position' => ScoringAlgorithm::NEEDS_POSITION,
                'time_seconds' => 0,
                'time_behind' => null,
                'points_final' => 100,
                'upload_type' => UploadTypes::RANKING_COMPUTED,
                'note' => null,
                'status_code' => null,
                'is_nc' => null,
                'contributory' => null,
            ],
        ];
        $this->assertEquals($expectedOveralls, $overalls);
    }
}
