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
}
