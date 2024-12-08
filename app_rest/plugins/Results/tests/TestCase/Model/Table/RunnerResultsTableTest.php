<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Table;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\Event;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Stage;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\SplitsTable;
use Results\Test\Fixture\RunnerResultsFixture;
use Results\Test\Fixture\RunnersFixture;
use Results\Test\Fixture\SplitsFixture;
use Results\Test\Fixture\StagesFixture;
use Results\Test\Fixture\TeamResultsFixture;

class RunnerResultsTableTest extends TestCase
{
    protected $fixtures = [
        RunnersFixture::LOAD,
        RunnerResultsFixture::LOAD,
        TeamResultsFixture::LOAD,
        SplitsFixture::LOAD,
    ];
    /** @var RunnerResultsTable Runners */
    private $RunnerResults;

    public function setUp(): void
    {
        parent::setUp();
        $this->RunnerResults = RunnerResultsTable::load();
    }

    public function testPatchNewWithStage()
    {
        $data = [
            'id' => '',
            'stage_order' => '1',
            'start_time' => '2014-07-06T13:09:01.523',
            'status_code' => '0',
            'leg_number' => '1',
            'result_type' => [
                'id' => 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e',
                'description' => 'Stage'
            ]
        ];
        $res = $this->RunnerResults->fillNewWithStage($data, Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2);
        $this->assertEquals($data['stage_order'], $res->stage_order);
        $this->assertEquals($data['leg_number'], $res->leg_number);
        $this->assertEquals($data['status_code'], $res->status_code);
        $this->assertEquals('2014-07-06T13:09:01+00:00', $res->start_time->toIso8601String());
        //$this->assertEquals('2014-07-06T13:09:01+00:00', $res->start_time->toIso8601String());
    }

    public function testHasFinishTimes(): void
    {
        $runners = $this->RunnerResults->hasFinishTimes(Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $this->assertTrue($runners);

        $runners = $this->RunnerResults->hasFinishTimes(Event::FIRST_EVENT, StagesFixture::STAGE_FEDO_2);
        $this->assertFalse($runners);
    }

    public function testFindWithSplits()
    {
        /** @var RunnerResult $res */
        $res = $this->RunnerResults->find()
            ->where(['id' => RunnerResult::FIRST_RES])
            ->contain(SplitsTable::name())
            ->firstOrFail();
        $array = json_decode(json_encode($res), true);
        $expected = [
            'id' => RunnerResult::FIRST_RES,
            'result_type_id' => ResultType::STAGE,
            'start_time' => '2024-01-02T10:00:00.000+00:00',
            'finish_time' => '2024-01-02T10:05:10.123+00:00',
            'time_seconds' => 310,
            'position' => 1,
            'status_code' => null,
            'time_behind' => 0,
            'time_neutralization' => null,
            'time_adjusted' => null,
            'time_penalty' => null,
            'time_bonus' => null,
            'points_final' => null,
            'points_adjusted' => null,
            'points_penalty' => null,
            'points_bonus' => null,
            'leg_number' => null,
            'splits' => [
                [
                    'id' => SplitsFixture::SPLIT_1,
                    'reading_time' => '2024-01-02T10:00:10.321+00:00',
                    'points' => null,
                    'order_number' => null
                ]
            ],
        ];
        $this->assertEquals($expected, $array);
    }
}
