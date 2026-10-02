<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Lib\Consts\StatusCode;
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

    public function testGetSplitsWithoutRadios_shouldKeepAMissingControlOfAHandValidatedRunner()
    {
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'handValidated';
        $runnerResult->position = 1;
        $runnerResult->addSplit($this->_normalSplit('before', '32', 9, '2026-09-06 16:40:01'));
        $runnerResult->addSplit($this->_normalSplit('missing', '68', 10, null));
        $runnerResult->addSplit($this->_normalSplit('after', '38', 11, '2026-09-06 16:41:25'));

        $kept = array_map(fn(Split $split) => $split->id, $runnerResult->getSplitsWithoutRadios());

        $this->assertContains('missing', $kept,
            'an organiser validating a runner by hand gives him a position while a control stays genuinely '
            . 'missing, so a timeless split on a positioned result is not necessarily a duplicate');
        $this->assertNotContains('missing', $runnerResult->getSplitsToRemove(),
            'the results endpoints soft-delete whatever this hides, so hiding a real control loses it');
    }

    public function testGetSplitsWithoutRadios_shouldStillHideATimelessCopyOfATimedControl()
    {
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'storedTwice';
        $runnerResult->position = 1;
        $runnerResult->addSplit($this->_normalSplit('timed', '68', 10, '2026-09-06 16:40:30'));
        $runnerResult->addSplit($this->_normalSplit('copy', '68', 10, null));

        $kept = array_map(fn(Split $split) => $split->id, $runnerResult->getSplitsWithoutRadios());

        $this->assertSame(['timed'], $kept,
            'issue 45 is the same control stored twice by separate uploads, one of them without its time: '
            . 'the control is still shown with its time, so the timeless copy adds nothing but a broken split');
    }

    public function testCleanSplitsWithoutRadios_shouldHideTheRadiosOfARunnerWhoReachedNoneYet()
    {
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'notReachedAnyRadio';
        $runnerResult->addSplit($this->_radioSplit('radio59', '59', null));
        $runnerResult->addSplit($this->_radioSplit('radio47', '47', null));

        $runnerResult->cleanSplitsWithoutRadios();

        $this->assertSame([], $runnerResult->getSplits(),
            'OE v12 exports every declared radio for every runner, so a runner who reached none yet '
            . 'carries only timeless radios, which the frontend shows as missed controls');
    }

    public function testCleanSplitsWithoutRadios_shouldNotAddSplitsToAResultLoadedWithoutThem()
    {
        $runnerResult = new RunnerResult();
        $runnerResult->id = 'splitsNotContained';

        $runnerResult->cleanSplitsWithoutRadios();

        $this->assertArrayNotHasKey('splits', $runnerResult->toArray());
    }

    private function _radioSplit(string $id, string $station, ?string $readingTime): Split
    {
        $split = new Split();
        $split->id = $id;
        $split->is_intermediate = true;
        $split->station = $station;
        $split->reading_time = $readingTime ? new FrozenTime($readingTime) : null;
        return $split;
    }

    private function _normalSplit(string $id, string $station, int $orderNumber, ?string $readingTime): Split
    {
        $split = new Split();
        $split->id = $id;
        $split->is_intermediate = false;
        $split->station = $station;
        $split->order_number = $orderNumber;
        $split->reading_time = $readingTime ? new FrozenTime($readingTime) : null;
        return $split;
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
        $this->assertFalse($runnerResult->hasInvalidFinishTime());
        $runnerResult->status_code = StatusCode::OK;
        $runnerResult->finish_time = new FrozenTime();
        $this->assertTrue($runnerResult->hasInvalidFinishTime());
        $runnerResult->time_seconds = 250;
        $this->assertFalse($runnerResult->hasInvalidFinishTime());
    }
}
