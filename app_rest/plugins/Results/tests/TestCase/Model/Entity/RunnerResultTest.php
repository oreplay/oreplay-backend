<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\I18n\FrozenTime;
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
        $readingTime = '2025-05-21T09:50:00.000+00:00';
        $firstExpected = [
            'id' => 'downloadID1',
            'is_intermediate' => false,
            'reading_time' => $readingTime,
        ];

        $runnerResult = new RunnerResult();
        $runnerResult->id = 'mainID';
        $runnerResult->position = 1;

        $this->assertEquals([], $runnerResult->getSplitsWithoutRadios());

        $split = new Split();
        $split->id = 'downloadID1';
        $split->is_intermediate = false;
        $split->reading_time = new FrozenTime($readingTime);
        $runnerResult->addSplit($split);

        $this->assertEquals([$firstExpected], $this->_getSplitsWithoutRadios($runnerResult));

        $split = new Split();
        $split->id = 'radioID1';
        $split->is_intermediate = true;
        $split->reading_time = new FrozenTime('2025-05-21 09:50:00');
        $runnerResult->addSplit($split);

        $this->assertEquals([$firstExpected], $this->_getSplitsWithoutRadios($runnerResult));

        $split = new Split();
        $split->id = 'downloadIDnoTime';
        $split->is_intermediate = false;
        $split->reading_time = null;
        $runnerResult->addSplit($split);

        $this->assertEquals([$firstExpected], $this->_getSplitsWithoutRadios($runnerResult));

        // when reading time is null we should not return this radio
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'mainID';
        $runnerResult->position = 0;
        $split1 = new Split();
        $split1->id = 'downloadID1';
        $split1->is_intermediate = true;
        $split1->reading_time = null;
        $runnerResult->addSplit($split1);

        $split2 = new Split();
        $split2->id = 'downloadID1';
        $split2->is_intermediate = true;
        $split2->reading_time = new FrozenTime('2025-05-21 09:50:00');
        $runnerResult->addSplit($split2);

        $this->assertEquals([$split2], $runnerResult->getSplitsWithoutRadios());


    }

    private function _getSplitsWithoutRadios(RunnerResult $runnerResult)
    {
        return json_decode(json_encode($runnerResult->getSplitsWithoutRadios()), true);
    }

    public function testHasInvalidFinishTime()
    {
        $runnerResult = new RunnerResult();
        $this->assertFalse($runnerResult->hasInvalidFinishTime());
        $runnerResult->finish_time = new FrozenTime();
        $this->assertTrue($runnerResult->hasInvalidFinishTime());
        $runnerResult->time_seconds = 250;
        $this->assertFalse($runnerResult->hasInvalidFinishTime());
    }
}
