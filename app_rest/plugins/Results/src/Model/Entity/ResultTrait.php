<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\Consts\StatusCode;

trait ResultTrait
{
    private array $_splitsToRemove = [];
    private bool $_compareWithoutDay = false;
    public function setCompareWithoutDay(bool $compareWithoutDay): void
    {
        $this->_compareWithoutDay = $compareWithoutDay;
    }

    public function cleanSplitsWithoutRadios(): void
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
        $this->_splitsToRemove = [];
        $countRadios = 0;
        $splitsToRet = [];
        /** @var Split $lastSplit */
        $lastSplit = null;
        /** @var Split $split */
        foreach ($this->getSplits() as $split) {
            if ($split->isRadioWithoutTime()) {
                // skip split if is radio without reading time
                continue;
            }
            if ($lastSplit) {
                $reason = $split->compareWithoutDay($this->_compareWithoutDay)->shouldDisplayCurrent($lastSplit);
                $split->setReason($reason);
                if ($reason->shouldDisplay() && !$this->_hasPositionButNoTime($split)) {
                    // skip split if it has position (all controls ok) and no reading_time
                    $lastSplit = $split;
                    $splitsToRet[] = $lastSplit;
                    if ($lastSplit->isRadio()) {
                        $countRadios++;
                    }
                } else {
                    $this->_splitsToRemove[] = $split->id;
                }
            } else {
                if (!$this->_hasPositionButNoTime($split)) {
                    $lastSplit = $split;
                    $splitsToRet[] = $lastSplit;
                    if ($lastSplit->isRadio()) {
                        $countRadios++;
                    }
                }
            }
        }
        $countNoRadios = count($splitsToRet) - $countRadios;
        $wasRunnerAlreadyDownloaded = $countNoRadios > $countRadios;
        if ($countRadios && $wasRunnerAlreadyDownloaded) {
            /** @var Split $split */
            foreach ($splitsToRet as $k => $split) {
                if ($split->isRadio()) {
                    unset($splitsToRet[$k]);
                }
            }
            $splitsToRet = array_values($splitsToRet);
        }
        return $splitsToRet;
    }

    public function getSplitsToRemove(): array
    {
        return $this->_splitsToRemove;
    }

    private function _hasPositionButNoTime(Split $s): bool
    {
        // has position (all controls ok) and no reading_time (one control is not ok)
        return $this->position && !$s->reading_time;
    }

    /**
     * @return Split[]
     */
    public function getSplits(): array
    {
        return $this->_fields['splits'] ?? [];
    }

    public function replaceSplits(array $splits)
    {
        $this->_fields['splits'] = $splits;
    }

    public function hasInvalidFinishTime(): bool
    {
        if ($this->status_code !== StatusCode::OK) {
            return false;
        }
        return !$this->time_seconds && $this->finish_time instanceof FrozenTime;
    }

    public function isResultTypeStage(): bool
    {
        if (!$this->result_type_id && $this->result_type) {
            return $this->result_type->id === ResultType::STAGE;
        }
        return $this->result_type_id === ResultType::STAGE;
    }
}
