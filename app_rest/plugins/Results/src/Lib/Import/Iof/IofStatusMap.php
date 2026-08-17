<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;
use RestApi\Lib\Exception\DetailedException;

/**
 * The single place the IOF status vocabulary meets ours. See docs/upload-xml-input.md 2B for what the
 * Java desktop client does with the same values, and where this deliberately differs.
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
        // out on the course. SportSoftware writes Inactive for this rather than the standard's "not yet
        // started", so both land here; a runner who truly never started arrives as DidNotStart instead
        ResultStatus::ACTIVE => StatusCode::RUNNING,
        ResultStatus::INACTIVE => StatusCode::RUNNING,
        // gave up the race to help someone, which finishes the race without a valid result
        ResultStatus::SPORTING_WITHDRAWAL => StatusCode::DNF,
        // never took the start in this class, whether or not they ran in another one
        ResultStatus::CANCELLED => StatusCode::DNS,
        ResultStatus::MOVED => StatusCode::DNS,
        ResultStatus::MOVED_UP => StatusCode::DNS,
    ];

    public static function codeOf(string $iofStatus): string
    {
        // array_key_exists, not ?? or a truthy test: StatusCode::OK is '0', which is falsy
        if (!array_key_exists($iofStatus, self::CODE_OF_IOF_STATUS)) {
            throw new DetailedException('Unknown IOF result status: ' . $iofStatus);
        }
        return self::CODE_OF_IOF_STATUS[$iofStatus];
    }
}
