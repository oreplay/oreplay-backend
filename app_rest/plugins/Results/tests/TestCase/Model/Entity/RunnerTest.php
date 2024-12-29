<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Results\Model\Entity\Runner;

class RunnerTest extends TestCase
{
    public function test_getFullName()
    {
        $runnerResult = new Runner();
        $runnerResult->id = 'mainID';
        $runnerResult->first_name = 'First';
        $runnerResult->last_name = 'Last';
        $runnerResult->created = new FrozenTime();

        $this->assertEquals('First Last', $runnerResult->_getFullName());

        $runnerResult->created = new FrozenTime('-1 year -1 day');
        $this->assertEquals('F. L.', $runnerResult->_getFullName());
    }
}
