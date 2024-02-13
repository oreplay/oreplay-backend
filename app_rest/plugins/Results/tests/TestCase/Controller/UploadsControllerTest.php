<?php

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Table\RunnersTable;
use Results\Test\Fixture\ClassesFixture;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\ControlsFixture;
use Results\Test\Fixture\ControlTypesFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;

class UploadsControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
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
            . StagesFixture::STAGE_FEDO_2 . '/uploads/';
    }

    public function testAddNew_shouldAddStartDates()
    {
        $data = [
            [
                'first_name' => 'Imported',
                'last_name' => 'Runner',
                'runner_results' => [
                    'start_time' => '2024-01-02T11:00:00+00:00',
                ]
            ],
            [
                'first_name' => 'Imported',
                'last_name' => 'Second',
                'runner_results' => [
                    'start_time' => '2024-01-02T11:01:00+00:00',
                ]
            ],
        ];
        $this->post($this->_getEndpoint(), $data);

        $jsonDecoded = $this->assertJsonResponseOK()['data'];

        $res = RunnersTable::load()
            ->findRunnersInStage(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2)
            ->orderAsc('last_name')
            ->all();

        /** @var Runner $value */
        foreach ($res as $key => $value) {
            $this->assertEquals($jsonDecoded[$key]['first_name'], $value->first_name);
            $this->assertEquals($jsonDecoded[$key]['last_name'], $value->last_name);
            $this->assertEquals($jsonDecoded[$key]['id'], $value->id);
            $this->assertEquals($jsonDecoded[$key]['runner_results'][0]['start_time'],
                $value->runner_results[0]->start_time->jsonSerialize());
            $this->assertEquals($jsonDecoded[$key]['runner_results'][0]['id'],
                $value->runner_results[0]->id);
            $this->assertEquals(ResultType::STAGE,
                $value->runner_results[0]->result_type_id);
        }
    }
}
