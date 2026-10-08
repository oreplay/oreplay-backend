<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;

/**
 * The single place the IOF status vocabulary meets ours. It copies on purpose what the Java desktop
 * client does (Utils.convertIofStatusValue), so the same XML ends up with the same status_code whether
 * the client or this backend reads it. See "Estados IOF al importar XML" in CLAUDE.md.
 */
class IofStatusMap
{
    private const CODE_OF_IOF_STATUS = [
        ResultStatus::OK => StatusCode::OK,
        ResultStatus::FINISHED => StatusCode::FINISHED,
        ResultStatus::MISSING_PUNCH => StatusCode::MP,
        ResultStatus::DISQUALIFIED => StatusCode::DQF,
        ResultStatus::DID_NOT_FINISH => StatusCode::DNF,
        ResultStatus::DID_NOT_START => StatusCode::DNS,
        ResultStatus::OVER_TIME => StatusCode::OT,
        ResultStatus::NOT_COMPETING => StatusCode::NC,
    ];

    /**
     * Anything else, Active, Inactive, SportingWithdrawal, Cancelled, Moved, MovedUp or a value outside
     * the standard, is OK, as in the desktop client
     */
    public static function codeOf(string $iofStatus): string
    {
        // array_key_exists, not a truthy test: StatusCode::OK is '0', which is falsy
        if (!array_key_exists($iofStatus, self::CODE_OF_IOF_STATUS)) {
            return StatusCode::OK;
        }
        return self::CODE_OF_IOF_STATUS[$iofStatus];
    }
}
