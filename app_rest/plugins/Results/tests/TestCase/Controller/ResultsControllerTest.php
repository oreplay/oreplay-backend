<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\TeamResultsFixture;
use Results\Test\Fixture\TeamsFixture;

class ResultsControllerTest extends ApiCommonErrorsTest
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
        TeamsFixture::LOAD,
        TeamResultsFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/results/';
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstRunner(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);
    }

    public function testGetList_filteredByExistingClass()
    {
        $this->get($this->_getEndpoint() . '?class_id='.ClassEntity::ME);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstRunner(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);
    }

    public function testGetList_filteredByNotExistingClass()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?class_id=NOT_EXISTING_CLASS');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals([], $bodyDecoded['data']);
    }

    public function testGetList_filteredByExistingClub()
    {
        $this->get($this->_getEndpoint() . '?club_id='.ClubsFixture::CLUB_1);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstRunner(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);
    }

    public function testGetList_filteredByName()
    {
        $this->get($this->_getEndpoint() . '?text=Runner');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(1, count($bodyDecoded['data']));
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][0]);
    }

    public function testGetList_filteredByNotExistingClub()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?club_id=NOT_EXISTING_CLUB');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals([], $bodyDecoded['data']);
    }

    private function _getFirstRunner(): array
    {
        return [
            'id' => Team::FIRST_TEAM,
            'full_name' => 'First Team',
            'legs' => null,
            'bib_number' => '301',
            'club' => [
                'id' => ClubsFixture::CLUB_1,
                'short_name' => 'Club A',
            ],
            'class' => [
                'id' => ClassEntity::ME,
                'short_name' => 'ME',
                'long_name' => 'M Elite',
            ],
            'runners' => [],
            'overall' => [
                'id' => TeamResultsFixture::TEAM_RESULT_1,
                'result_type_id' => ResultType::OVERALL,
                'start_time' => '2024-01-03T10:10:00.000+00:00',
                'finish_time' => '2024-01-03T10:15:10.000+00:00',
                'time_seconds' => 310,
                'position' => 1,
                'status_code' => null,
                'time_behind' => 0,
                'points_final' => null,
                'time_neutralization' => null,
                'time_adjusted' => null,
                'time_penalty' => null,
                'time_bonus' => null,
                'points_adjusted' => null,
                'points_penalty' => null,
                'points_bonus' => null,
                //'leg_number' => null,
                'splits' => [
                    [
                        'id' => SplitsFixture::SPLIT_2,
                        'points' => null,
                        'reading_time' => '2024-01-03T11:00:20.321+00:00',
                        'order_number' => null,
                        'is_intermediate' => false,
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
        ];
    }

    private function _getSecondRunner(): array
    {
        $overall = [
            'id' => RunnerResult::FIRST_RES,
            'result_type_id' => ResultType::STAGE,
            'start_time' => '2024-01-02T10:00:00.000+00:00',
            'finish_time' => '2024-01-02T10:05:10.123+00:00',
            'upload_type' => null,
            'time_seconds' => 310,
            'position' => 1,
            'status_code' => null,
            'time_behind' => 0,
            'points_final' => null,
            'time_neutralization' => null,
            'time_adjusted' => null,
            'time_penalty' => null,
            'time_bonus' => null,
            'points_adjusted' => null,
            'points_penalty' => null,
            'points_bonus' => null,
            'leg_number' => null,
            'splits' => [
                [
                    'id' => SplitsFixture::SPLIT_1,
                    'points' => null,
                    'reading_time' => '2024-01-02T10:00:10.321+00:00',
                    'order_number' => null,
                    'is_intermediate' => false,
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
        ];
        return [
            'id' => Runner::FIRST_RUNNER,
            'full_name' => 'First Runner',
            'first_name' => 'First',
            'last_name' => 'Runner',
            'sicard' => '2009933',
            'bib_number' => '4444',
            'sex' => 'F',
            'club' => [
                'id' => ClubsFixture::CLUB_1,
                'short_name' => 'Club A',
            ],
            'class' => [
                'id' => ClassEntity::ME,
                'short_name' => 'ME',
                'long_name' => 'M Elite',
            ],
            'overall' => $overall,
            'runner_results' => [$overall],
        ];
    }
}
