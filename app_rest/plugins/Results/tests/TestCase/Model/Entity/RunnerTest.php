<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;

class RunnerTest extends TestCase
{
    public function test_getFullName()
    {
        $runnerResult = new Runner();
        $runnerResult->id = 'mainID';
        $runnerResult->first_name = 'First';
        $runnerResult->last_name = 'Last';
        $runnerResult->created = new FrozenTime();

        $this->assertEquals('First Last', $runnerResult->_getFullName());

        $runnerResult->created = new FrozenTime('-1 year -1 day');
        $this->assertEquals('F. L.', $runnerResult->_getFullName());
    }

    public function testGetMatchedRunner()
    {
        $runnerResult = new Runner();
        $runnerResult->id = 'mainID';
        $runnerResult->first_name = 'First';
        $runnerResult->last_name = 'Last';
        $runnerResult->created = new FrozenTime();
        // we should allow any relay runner to be empty (but leg has to be defined)
        $runnerData = [
            'first_name' => '',
            'last_name' => 'nn',
            'runner_results' => [
                [
                    'leg_number' => 1,
                ]
            ],
        ];
        $this->assertNull($runnerResult->getMatchedRunner($runnerData));
        // if no nn defined as lastname, we throw exception
        $runnerData = [
            'first_name' => '',
            'last_name' => '',
            'runner_results' => [
                [
                    'leg_number' => 1,
                ]
            ],
        ];
        $exception = 'no exception';
        try {
            $runnerResult->getMatchedRunner($runnerData);
        } catch (DetailedException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Fields first_name [] and last_name [] cannot be empty', $exception);
        // if no leg defined we throw exception
        $runnerData = [
            'first_name' => '',
            'last_name' => 'nn',
            'runner_results' => [
            ],
        ];
        $exception = 'no exception';
        try {
            $runnerResult->getMatchedRunner($runnerData);
        } catch (DetailedException $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals('Fields first_name [] and last_name [nn] cannot be empty', $exception);
    }

    public function testGetStages()
    {
        $runnerResult1 = new RunnerResult();
        $runnerResult1->result_type_id = ResultType::PARTIAL_OVERALL;
        $runnerResult1->stage_order = 3;
        $runnerResult1->position = 2;
        $runnerResult1->time_seconds = 265;
        $runnerResult1->points_final = 854;

        $runnerResult2 = new RunnerResult();
        $runnerResult2->result_type_id = ResultType::PARTIAL_OVERALL;
        $runnerResult2->stage_order = 2;
        $runnerResult2->position = 1;
        $runnerResult2->time_seconds = 234;
        $runnerResult2->points_final = 895;

        $runner = new Runner();
        $runner->runner_results = [
            $runnerResult1,
            $runnerResult2,
        ];

        $expected = [
            'parts' => [
                [
                    'id' => null,
                    'stage_order' => 2,
                    'position' => 1,
                    'time_seconds' => 234,
                    'points_final' => 895,
                ],
                [
                    'id' => null,
                    'stage_order' => 3,
                    'position' => 2,
                    'time_seconds' => 265,
                    'points_final' => 854,
                ],
            ],
            'overall' => null,
        ];
        $this->assertEquals($expected, $runner->_getOveralls());
        $this->assertEquals(null, $runner->_getStage());
    }
}
