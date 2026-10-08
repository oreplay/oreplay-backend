<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Import\Iof\IofStatusMap;

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
     * As the desktop client does. SportSoftware writes Inactive for a runner who has started and is still
     * out on the course, so mid-race they show as OK; by the final export they are OK, DNS or MP.
     */
    public function testCodeOf_shouldMapActiveAndInactiveToOkLikeTheDesktopClient()
    {
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::INACTIVE));
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::ACTIVE));
    }

    public function testCodeOf_shouldMapTheStatusesTheDesktopClientDoesNotKnowToOk()
    {
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::SPORTING_WITHDRAWAL));
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::CANCELLED));
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::MOVED));
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(ResultStatus::MOVED_UP));
    }

    public function testCodeOf_shouldCoverEveryStatusTheStandardDefines()
    {
        $reflection = new \ReflectionClass(ResultStatus::class);
        foreach ($reflection->getConstants() as $name => $iofStatus) {
            // not assertNotEmpty: StatusCode::OK is '0', which counts as empty
            $this->assertSame(1, strlen(IofStatusMap::codeOf($iofStatus)), $name . ' has no code');
        }
    }

    public function testCodeOf_shouldMapAStatusOutsideTheStandardToOkInsteadOfFailing()
    {
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf('Sleeping'));
        $this->assertSame(StatusCode::OK, IofStatusMap::codeOf(''));
    }
}
