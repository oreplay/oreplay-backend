<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

trait ResultTrait
{
    private bool $_compareWithoutDay = false;
    public function setCompareWithoutDay(bool $compareWithoutDay)
    {
        $this->_compareWithoutDay = $compareWithoutDay;
    }

    public function cleanSplitsWithoutRadios()
    {
        $splits = $this->getSplitsWithoutRadios();
        if ($splits) {
            $this->replaceSplits($splits);
        }
    }

    /**
     * @return Split[]
     */
    public function getSplitsWithoutRadios(): array
    {
        $splitsToRet = [];
        /** @var Split $lastSplit */
        $lastSplit = null;
        foreach ($this->getSplits() as $split) {
            if ($lastSplit) {
                $reason = $split->compareWithoutDay($this->_compareWithoutDay)->shouldDisplayCurrent($lastSplit);
                if ($reason->shouldDisplay() && !$this->_hasPositionButNoTime($split)) {
                    // skip split if it has position (all controls ok) and no reading_time
                    $lastSplit = $split;
                    $splitsToRet[] = $lastSplit;
                }
            } else {
                if (!$this->_hasPositionButNoTime($split)) {
                    $lastSplit = $split;
                    $splitsToRet[] = $lastSplit;
                }
            }
        }
        return $splitsToRet;
    }

    private function _hasPositionButNoTime(Split $s): bool
    {
        // has position (all controls ok) and no reading_time (one control is not ok)
        return $this->position && !$s->reading_time;
    }
}
