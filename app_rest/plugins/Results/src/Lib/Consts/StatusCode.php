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
    //public const NC = '9'; // not competitive
}
