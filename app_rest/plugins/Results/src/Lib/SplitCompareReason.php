<?php

declare(strict_types = 1);

namespace Results\Lib;

class SplitCompareReason
{
    private bool $_shouldDisplay;
    private string $_reason;

    public function __construct(bool $shouldDisplay, string $reason)
    {
        $this->_shouldDisplay = $shouldDisplay;
        $this->_reason = $reason;
    }

    public function reason(): string
    {
        return $this->_reason;
    }

    public function shouldDisplay(): bool
    {
        return $this->_shouldDisplay;
    }
}
