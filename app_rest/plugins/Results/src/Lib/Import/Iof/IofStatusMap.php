<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

use RestApi\Lib\Exception\DetailedException;
use Results\Lib\Consts\ResultStatus;
use Results\Lib\Consts\StatusCode;

/**
 * The single place the IOF status vocabulary meets ours. It follows the Java desktop client
 * (Utils.convertIofStatusValue), so the same XML ends up with the same status_code whether the client or
 * this backend reads it, except for the statuses the client does not know and would call OK: a sporting
 * withdrawal is DNF, and a cancelled or moved entry is DNS, because the frontend shows an OK without a
 * finish time as a runner still on the course.
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
        // out on the course, which the frontend shows as an OK without a finish time, as the desktop client
        // sends it. SportSoftware writes Inactive for this rather than the standard's "not yet started"; a
        // runner who truly never started arrives as DidNotStart instead
        ResultStatus::ACTIVE => StatusCode::OK,
        ResultStatus::INACTIVE => StatusCode::OK,
        // gave up the race to help someone, which finishes the race without a valid result
        ResultStatus::SPORTING_WITHDRAWAL => StatusCode::DNF,
        // never took the start in this class, whether or not they ran in another one
        ResultStatus::CANCELLED => StatusCode::DNS,
        ResultStatus::MOVED => StatusCode::DNS,
        ResultStatus::MOVED_UP => StatusCode::DNS,
    ];

    /**
     * Every standard status is mapped, so only a value outside the standard is refused, which the schema
     * already does unless the upload skips validation.
     */
    public static function codeOf(string $iofStatus): string
    {
        // array_key_exists, not a truthy test: StatusCode::OK is '0', which is falsy
        if (!array_key_exists($iofStatus, self::CODE_OF_IOF_STATUS)) {
            throw new DetailedException('Unknown IOF result status: ' . $iofStatus);
        }
        return self::CODE_OF_IOF_STATUS[$iofStatus];
    }
}
