<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;

/**
 * IOF puts "not competing" in the Status slot, so a runner who is both not competing *and* mispunched
 * cannot be expressed: the real status is destroyed. oreplay keeps the two apart (is_nc beside
 * status_code), and this recovers the lost half the way the Java desktop client does — see
 * docs/upload-xml-input.md 2B.
 */
class NcStatusReconstructor
{
    public static function isNotCompeting(?string $iofStatus): bool
    {
        return $iofStatus === ResultStatus::NOT_COMPETING;
    }

    /**
     * @param bool $hasMissingSplit true when any split of the result is flagged Missing, which is the
     *                              only remaining trace that a not-competing runner mispunched
     */
    public static function codeOf(
        ?string $iofStatus,
        ?int $position,
        bool $hasStartTime,
        bool $hasFinishTime,
        bool $hasMissingSplit
    ): string {
        // a result with no Status at all is not invalid input, only incomplete: infer it the same way,
        // rather than crashing a whole upload over one row or calling it OK as the desktop client does
        if ($iofStatus === null || $iofStatus === '') {
            return self::_inferredCode($position, $hasStartTime, $hasFinishTime);
        }
        if (!self::isNotCompeting($iofStatus)) {
            return IofStatusMap::codeOf($iofStatus);
        }
        $code = self::_inferredCode($position, $hasStartTime, $hasFinishTime);
        if ($code === StatusCode::OK && $hasMissingSplit) {
            return StatusCode::MP;
        }
        return $code;
    }

    private static function _inferredCode(?int $position, bool $hasStartTime, bool $hasFinishTime): string
    {
        if ($position !== null && $position > 0) {
            return StatusCode::OK;
        }
        if ($hasStartTime && $hasFinishTime) {
            return StatusCode::OK;
        }
        if (!$hasStartTime && !$hasFinishTime) {
            return StatusCode::DNS;
        }
        if ($hasStartTime) {
            return StatusCode::DNF;
        }
        // a finish without a start says nothing either way, so keep the one thing that is certain
        return StatusCode::OK;
    }
}
