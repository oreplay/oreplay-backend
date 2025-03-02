<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Entity;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Split;

class RunnerResultTest extends TestCase
{
    public function testAddSplit()
    {
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'mainID';
        $split = new Split();
        $split->id = 'splitID';

        $runnerResult->addSplit($split);

        $this->assertEquals($split->id, $runnerResult->getSplits()[0]->id);
    }

    public function testGetSplitsWithoutRadios()
    {
        $firstExpected = [
            'id' => 'downloadID1',
            'is_intermediate' => false,
        ];

        $runnerResult = new RunnerResult();
        $runnerResult->id = 'mainID';

        $this->assertEquals([], $runnerResult->getSplitsWithoutRadios());

        $split = new Split();
        $split->id = 'downloadID1';
        $split->is_intermediate = false;
        $runnerResult->addSplit($split);

        $this->assertEquals([$firstExpected], $this->_getSplitsWithoutRadios($runnerResult));

        $split = new Split();
        $split->id = 'radioID1';
        $split->is_intermediate = true;
        $runnerResult->addSplit($split);

        $this->assertEquals([$firstExpected], $this->_getSplitsWithoutRadios($runnerResult));
    }

    private function _getSplitsWithoutRadios(RunnerResult $runnerResult)
    {
        return json_decode(json_encode($runnerResult->getSplitsWithoutRadios()), true);
    }
}
