<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Model\Entity;

use Cake\TestSuite\TestCase;
use Results\Model\Entity\PartialOverall;
use Results\Model\Entity\RunnerResult;

class PartialOverallTest extends TestCase
{
    public function testGetFromResult()
    {
        $res = new RunnerResult();
        $res->id = 'theID';
        $res->stage_order = 3;
        $res->position = 4;
        $res->time_seconds = 5;
        $res->points_final = 6;

        $overall = PartialOverall::fromResult($res);
        $this->assertEquals('theID', $overall->id);
        $this->assertEquals(3, $overall->stage_order);
        $this->assertEquals(4, $overall->position);
        $this->assertEquals(5, $overall->time_seconds);
        $this->assertEquals(6, $overall->points_final);
    }
}
