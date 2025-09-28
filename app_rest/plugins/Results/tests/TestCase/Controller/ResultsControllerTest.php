<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Cake\Datasource\ResultSetInterface;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ControlType;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Entity\Team;
use Results\Model\Table\SplitsTable;
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
        $splits = $this->_getAllSplits()->count();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstTeam(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);

        $this->assertEquals($splits - 1, $this->_getAllSplits()->count());
    }

    private function _getAllSplits(): ResultSetInterface
    {
        return SplitsTable::load()->find()->all();
    }

    public function testGetList_filteredByExistingClass()
    {
        $this->get($this->_getEndpoint() . '?class_id='.ClassEntity::ME);

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstTeam(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);
    }

    public function testGetList_filteredByNotExistingClass()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?class_id=NOT_EXISTING_CLASS');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals([], $bodyDecoded['data']);
    }

    public function testGetList_filteredByExistingClubAndStation()
    {
        $this->get($this->_getEndpoint() . '?club_id='.ClubsFixture::CLUB_1 . '&station=31');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $this->assertEquals($this->_getFirstTeam(), $bodyDecoded['data'][0]);
        $this->assertEquals($this->_getSecondRunner(), $bodyDecoded['data'][1]);
    }

    public function testGetList_filteredByStation()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?&station=3145');

        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals(2, count($bodyDecoded['data']));
        $expectedTeam = $this->_getFirstTeam();
        $expectedTeam['stage']['splits'] = [];
        $this->assertEquals($expectedTeam, $bodyDecoded['data'][0]);
        $expected = $this->_getSecondRunner();
        $expected['stage']['splits'] = [];
        $this->assertEquals($expected, $bodyDecoded['data'][1]);
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

    private function _getFirstTeam(): array
    {
        $stage = [
            'id' => TeamResultsFixture::TEAM_RESULT_1,
            'result_type_id' => ResultType::STAGE,
            'start_time' => '2024-01-03T10:10:00.000+00:00',
            'finish_time' => '2024-01-03T10:15:10.000+00:00',
            'time_seconds' => 310,
            'position' => 1,
            'status_code' => null,
            'is_nc' => false,
            'contributory' => null,
            'time_behind' => 0,
            'points_final' => null,
            'time_neutralization' => null,
            'time_adjusted' => null,
            'time_penalty' => null,
            'time_bonus' => null,
            'points_adjusted' => null,
            'points_penalty' => null,
            'points_bonus' => null,
            'leg_number' => 0,
            'note' => null,
            'upload_type' => null,
            //'leg_number' => null,
            'created' => '2024-01-03T10:15:05.000+00:00',
            'splits' => [
                [
                    'id' => SplitsFixture::SPLIT_2,
                    'points' => null,
                    'reading_time' => '2024-01-03T11:00:20.321+00:00',
                    'order_number' => null,
                    'is_intermediate' => false,
                    //'position' => 0,
                    //'time_behind' => 0,
                    'created' => '2024-01-03T10:00:10.000+00:00',
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
            'id' => Team::FIRST_TEAM,
            'full_name' => 'First Team',
            'legs' => null,
            'bib_number' => '301',
            'is_nc' => false,
            'eligibility' => '',
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
            'overalls' => null,
            'stage' => $stage,
            'created' => '2024-01-03T10:00:06.000+00:00',
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
            'is_nc' => false,
            'contributory' => null,
            'time_behind' => 0,
            'points_final' => null,
            'time_neutralization' => null,
            'time_adjusted' => null,
            'time_penalty' => null,
            'time_bonus' => null,
            'points_behind' => 0,
            'points_adjusted' => null,
            'points_penalty' => null,
            'points_bonus' => null,
            'note' => null,
            'leg_number' => null,
            'created' => '2024-01-02T10:05:05.000+00:00',
            'splits' => [
                [
                    'id' => SplitsFixture::SPLIT_1,
                    'points' => null,
                    'reading_time' => '2024-01-02T10:00:10.321+00:00',
                    'order_number' => null,
                    'is_intermediate' => false,
                    //'position' => 0,
                    //'time_behind' => 0,
                    'created' => '2024-01-02T10:00:10.000+00:00',
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
            'sicard' => '2009933',
            'bib_number' => '4444',
            'is_nc' => false,
            'eligibility' => '',
            'sex' => 'M',
            'leg_number' => null,
            'club' => [
                'id' => ClubsFixture::CLUB_1,
                'short_name' => 'Club A',
            ],
            'class' => [
                'id' => ClassEntity::ME,
                'short_name' => 'ME',
                'long_name' => 'M Elite',
            ],
            'overalls' => null,
            'stage' => $overall,
            'created' => '2025-01-02T10:00:05.000+00:00',
        ];
    }
}
