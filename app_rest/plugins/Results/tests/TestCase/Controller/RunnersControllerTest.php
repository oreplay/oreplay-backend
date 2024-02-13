<?php

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;

class RunnersControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        ClubsFixture::LOAD,
        ClassesFixture::LOAD,
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        SplitsFixture::LOAD,
        ControlsFixture::LOAD,
        ControlTypesFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/runners/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $expected = $this->_getFirstRunner();
        $this->assertEquals($expected, $bodyDecoded['data'][0]);
    }

    private function _getFirstRunner(): array
    {
        return [
            'id' => Runner::FIRST_RUNNER,
            'first_name' => 'First',
            'last_name' => 'Runner',
            'club' => [
                'id' => ClubsFixture::CLUB_1,
                'short_name' => 'Club A',
            ],
            'class' => [
                'id' => ClassesFixture::ME,
                'short_name' => 'ME',
            ],
            'runner_results' => [
                [
                    'id' => RunnerResult::FIRST_RES,
                    'result_type_id' => ResultType::STAGE,
                    'start_time' => '2024-01-02T10:00:00+00:00',
                    'finish_time' => '2024-01-02T10:05:10+00:00',
                    'time_seconds' => 310,
                    'position' => 1,
                    'status_code' => null,
                    'time_behind' => 0,
                    'points_final' => null,
                    'splits' => [
                        [
                            'id' => SplitsFixture::SPLIT_1,
                            'points' => null,
                            'reading_time' => '2024-01-02T10:00:10+00:00',
                            //'position' => 0,
                            //'time_behind' => 0,
                            'control' => [
                                'id' => ControlsFixture::CONTROL_31,
                                'station' => '31',
                                'control_type' => [
                                    'id' => ControlType::NORMAL,
                                    'description' => 'Normal Control',
                                ]
                            ]
                        ]
                    ],
                ],
            ],
        ];
    }
}
