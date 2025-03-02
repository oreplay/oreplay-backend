<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

trait ResultTrait
{

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
                $reason = $split->shouldDisplayCurrent($lastSplit);
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
