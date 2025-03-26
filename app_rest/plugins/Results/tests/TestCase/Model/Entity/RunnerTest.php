<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\Runner;

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
}
