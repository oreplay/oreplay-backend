<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Import\Iof;

use Cake\TestSuite\TestCase;
use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;
use Results\Lib\Import\Iof\NcStatusReconstructor;

class NcStatusReconstructorTest extends TestCase
{
    private function _codeOf(?int $position, bool $start, bool $finish, bool $missing = false): string
    {
        return NcStatusReconstructor::codeOf(ResultStatus::NOT_COMPETING, $position, $start, $finish, $missing);
    }

    public function testCodeOf_shouldPassAnOrdinaryStatusStraightThrough()
    {
        $this->assertEquals(StatusCode::MP,
            NcStatusReconstructor::codeOf(ResultStatus::MISSING_PUNCH, 1, true, true, false));
        $this->assertEquals(StatusCode::RUNNING,
            NcStatusReconstructor::codeOf(ResultStatus::INACTIVE, null, true, false, false));
    }

    public function testCodeOf_shouldReadAPositionAsProofTheRunnerFinished()
    {
        $this->assertEquals(StatusCode::OK, $this->_codeOf(3, false, false));
    }

    public function testCodeOf_shouldAssumeOkWhenBothTimesArePresent()
    {
        $this->assertEquals(StatusCode::OK, $this->_codeOf(null, true, true));
    }

    public function testCodeOf_shouldAssumeDidNotStartWhenNeitherTimeIsPresent()
    {
        $this->assertEquals(StatusCode::DNS, $this->_codeOf(null, false, false));
    }

    public function testCodeOf_shouldAssumeDidNotFinishWhenOnlyTheStartIsPresent()
    {
        $this->assertEquals(StatusCode::DNF, $this->_codeOf(null, true, false));
    }

    /**
     * The case IOF cannot express at all: NotCompeting occupies the status slot, so "not competing and
     * mispunched" survives only as a Missing split.
     */
    public function testCodeOf_shouldDowngradeAnInferredOkToMissingPunchWhenASplitIsMissing()
    {
        $this->assertEquals(StatusCode::MP, $this->_codeOf(null, true, true, true));
        $this->assertEquals(StatusCode::MP, $this->_codeOf(3, false, false, true));
    }

    public function testCodeOf_shouldNotDowngradeAnInferredDidNotStart()
    {
        $this->assertEquals(StatusCode::DNS, $this->_codeOf(null, false, false, true));
    }

    public function testIsNotCompeting_shouldOnlyMatchTheIofValue()
    {
        $this->assertTrue(NcStatusReconstructor::isNotCompeting(ResultStatus::NOT_COMPETING));
        $this->assertFalse(NcStatusReconstructor::isNotCompeting(ResultStatus::OK));
        $this->assertFalse(NcStatusReconstructor::isNotCompeting(null));
    }
}
