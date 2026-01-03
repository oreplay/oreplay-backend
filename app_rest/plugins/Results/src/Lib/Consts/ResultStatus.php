<?php

declare(strict_types = 1);

namespace Results\Lib\Consts;

/**
 * The result status of the person or team at the time of the result generation
 */
class ResultStatus
{
    public const string OK = 'OK'; // Finished and validated
    public const string FINISHED = 'Finished'; // Finished but not yet validated
    public const string MISSING_PUNCH = 'MissingPunch'; // Missing punch
    public const string DISQUALIFIED = 'Disqualified'; // Disqualified (for some other reason than a missing punch).
    // Did not finish (i.e. conciously cancelling the race after having started, in contrast to MissingPunch).
    public const string DID_NOT_FINISH = 'DidNotFinish';
    public const string ACTIVE = 'Active'; // Currently on course
    public const string INACTIVE = 'Inactive'; // Has not yet started
    public const string OVER_TIME = 'OverTime'; // i.e. did not finish within the max. time set by the organiser
    public const string SPORTING_WITHDRAWAL = 'SportingWithdrawal'; // (e.g. helping an injured competitor).
    public const string NOT_COMPETING = 'NotCompeting'; // Not competing (i.e. running outside the competition).
    public const string MOVED = 'Moved'; // Moved to another class
    public const string MOVED_UP = 'MovedUp'; // Moved to a "better" class, in case of entry restrictions
    public const string DID_NOT_START = 'DidNotStart'; // Did not start (in this race).
    public const string CANCELLED = 'Cancelled'; // The competitor has cancelled his/hers entry
}
