<?php

declare(strict_types = 1);

namespace Results\Lib;

use Results\Model\Entity\Split;

class TimedControls
{
    private array $_keys = [];

    /**
     * @param Split[] $splits
     */
    public function __construct(array $splits)
    {
        foreach ($splits as $split) {
            if ($split->reading_time && !$split->isRadio()) {
                $this->_keys[$this->_keyOf($split)] = true;
            }
        }
    }

    public function hasTimedCopyOf(Split $split): bool
    {
        return !$split->reading_time && isset($this->_keys[$this->_keyOf($split)]);
    }

    private function _keyOf(Split $split): string
    {
        return $split->station . '#' . $split->order_number;
    }
}
