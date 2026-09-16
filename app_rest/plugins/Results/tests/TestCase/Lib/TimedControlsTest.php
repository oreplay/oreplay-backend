<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Lib\TimedControls;
use Results\Model\Entity\Split;

class TimedControlsTest extends TestCase
{
    public function testHasTimedCopyOf_shouldFindATimelessSplitOfATimedControl()
    {
        $copy = $this->_split('68', 10, null);
        $timedControls = new TimedControls([$this->_split('68', 10, '2026-09-06 16:40:30'), $copy]);

        $this->assertTrue($timedControls->hasTimedCopyOf($copy),
            'issue 45 is the same control stored twice by separate uploads, one of them without its time');
    }

    public function testHasTimedCopyOf_shouldNotFindAControlMissingEverywhere()
    {
        $missing = $this->_split('68', 10, null);
        $timedControls = new TimedControls([
            $this->_split('32', 9, '2026-09-06 16:40:01'),
            $missing,
            $this->_split('38', 11, '2026-09-06 16:41:25'),
        ]);

        $this->assertFalse($timedControls->hasTimedCopyOf($missing),
            'a runner validated by hand keeps a genuinely missing control, which has no timed split anywhere');
    }

    public function testHasTimedCopyOf_shouldNotFindAMissedRevisitOfAControl()
    {
        $missedRevisit = $this->_split('100', 4, null);
        $timedControls = new TimedControls([
            $this->_split('100', 2, '2026-09-06 16:40:00'),
            $this->_split('60', 3, '2026-09-06 16:41:00'),
            $missedRevisit,
        ]);

        $this->assertFalse($timedControls->hasTimedCopyOf($missedRevisit),
            'a course may visit the same station twice, so only the same station at the same position in the '
            . 'course makes a copy');
    }

    public function testHasTimedCopyOf_shouldNotFindATimedSplit()
    {
        $timed = $this->_split('68', 10, '2026-09-06 16:40:30');
        $timedControls = new TimedControls([$timed]);

        $this->assertFalse($timedControls->hasTimedCopyOf($timed));
    }

    public function testHasTimedCopyOf_shouldIgnoreTimedRadios()
    {
        $downloaded = $this->_split('68', 10, null);
        $radio = $this->_split('68', 10, '2026-09-06 16:40:30');
        $radio->is_intermediate = true;
        $timedControls = new TimedControls([$radio, $downloaded]);

        $this->assertFalse($timedControls->hasTimedCopyOf($downloaded),
            'a radio punch is not a downloaded split, so it cannot make a downloaded one a copy');
    }

    private function _split(string $station, int $orderNumber, ?string $readingTime): Split
    {
        $split = new Split();
        $split->is_intermediate = false;
        $split->station = $station;
        $split->order_number = $orderNumber;
        $split->reading_time = $readingTime ? new FrozenTime($readingTime) : null;
        return $split;
    }
}
