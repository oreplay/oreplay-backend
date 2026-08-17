<?php

declare(strict_types = 1);

namespace Results\Lib\Consts;

class StatusCode
{
    public const OK = '0';
    public const DNS = '1'; //did not start
    public const DNF = '2'; //did not finish
    public const MP = '3'; // missing punch
    public const DQF = '4'; //disqualified
    public const OT = '5'; //out of time
    // started and still out on the course, so no result yet. Distinct from DNS on purpose: the same
    // upload carries genuine DidNotStart rows beside these
    public const RUNNING = '6';
    // finished but not yet validated. The desktop client has always emitted it; only the constant was
    // missing. Note it breaks the `status_code: type: number` claim in typescript/v1api.yaml, which was
    // already inaccurate before this
    public const FINISHED = 'F';
    public const NC = '9'; // not competitive
}
