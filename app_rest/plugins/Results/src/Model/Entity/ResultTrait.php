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
                if ($reason->shouldDisplay()) {
                    $lastSplit = $split;
                    $splitsToRet[] = $lastSplit;
                }
            } else {
                $lastSplit = $split;
                $splitsToRet[] = $lastSplit;
            }
        }
        return $splitsToRet;
    }
}
