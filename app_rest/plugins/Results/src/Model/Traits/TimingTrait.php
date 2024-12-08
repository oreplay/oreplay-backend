<?php

declare(strict_types = 1);

namespace Results\Model\Traits;

use Cake\Http\Exception\InternalErrorException;
use Results\Model\Entity\Runner;

trait TimingTrait
{
    private array $_sumTime = [];
    private array $_startTime = [];

    public function getTime(string $key): float
    {
        return round($this->_sumTime[$key] ?? 0, 3);
    }

    protected function startTimer(string $key)
    {
        $this->_startTime[$key] = microtime(true);
    }

    protected function endTimer(string $key)
    {
        if (!isset($this->_startTime[$key])) {
            throw new InternalErrorException('need to call startTimer() first');
        }
        if (!isset($this->_sumTime[$key])) {
            $this->_sumTime[$key] = 0;
        }
        $this->_sumTime[$key] += round(microtime(true) - $this->_startTime[$key], 2);
        $this->_startTime[$key] = null;
    }
}
