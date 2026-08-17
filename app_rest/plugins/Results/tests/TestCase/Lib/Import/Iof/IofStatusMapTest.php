<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Import\Iof\IofStatusMap;
use RestApi\Lib\Exception\DetailedException;

class IofStatusMapTest extends TestCase
{
    public function testCodeOf_shouldMapTheStatusesThatHaveADirectEquivalent()
    {
        $this->assertEquals(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::OK));
        $this->assertEquals(StatusCode::DNS, IofStatusMap::codeOf(ResultStatus::DID_NOT_START));
        $this->assertEquals(StatusCode::DNF, IofStatusMap::codeOf(ResultStatus::DID_NOT_FINISH));
        $this->assertEquals(StatusCode::MP, IofStatusMap::codeOf(ResultStatus::MISSING_PUNCH));
        $this->assertEquals(StatusCode::DQF, IofStatusMap::codeOf(ResultStatus::DISQUALIFIED));
        $this->assertEquals(StatusCode::OT, IofStatusMap::codeOf(ResultStatus::OVER_TIME));
        $this->assertEquals(StatusCode::FINISHED, IofStatusMap::codeOf(ResultStatus::FINISHED));
        $this->assertEquals(StatusCode::NC, IofStatusMap::codeOf(ResultStatus::NOT_COMPETING));
    }

    /**
     * The divergence from the desktop client, decided 2026-08-17. SportSoftware writes Inactive for a
     * runner who has started and is still out on the course, not for one who has not started: mid-race
     * every one of them has a start time, none has a finish time, and by the final export they have all
     * become OK, DNS or MP. The client has no branch for it, so today they arrive as OK.
     */
    public function testCodeOf_shouldMapInactiveToRunningRatherThanOk()
    {
        $this->assertEquals(StatusCode::RUNNING, IofStatusMap::codeOf(ResultStatus::INACTIVE));
        $this->assertEquals(StatusCode::RUNNING, IofStatusMap::codeOf(ResultStatus::ACTIVE));
    }

    public function testCodeOf_shouldCoverEveryStatusTheStandardDefines()
    {
        $reflection = new \ReflectionClass(ResultStatus::class);
        foreach ($reflection->getConstants() as $name => $iofStatus) {
            // not assertNotEmpty: StatusCode::OK is '0', which counts as empty
            $this->assertSame(1, strlen(IofStatusMap::codeOf($iofStatus)), $name . ' has no code');
        }
    }

    /**
     * Defaulting an unrecognised status to OK is how the desktop client loses Inactive; a value outside
     * the standard is invalid input and has to say so.
     */
    public function testCodeOf_shouldRejectAStatusOutsideTheStandard()
    {
        $this->expectException(DetailedException::class);
        $this->expectExceptionMessage('Unknown IOF result status: Sleeping');

        IofStatusMap::codeOf('Sleeping');
    }
}
