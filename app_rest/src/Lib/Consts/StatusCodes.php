<?php

namespace App\Lib\Consts;

class StatusCodes
{
    // Values as in OE, comments as in IOF XML
    public const OK = 0; // OK
    public const DNS = 1; // DidNotStart
    public const DNF = 1; // DidNotFinish
    public const MP = 3; // MissingPunch
    public const DSQ = 4; // Disqualified
    public const OVER_TIME = 5; // OverTime

    public const XML_OK = 'OK'; // OK
    public const XML_DNS = 'DidNotStart'; // DidNotStart
    public const XML_DNF = 'DidNotFinish'; // DidNotFinish
    public const XML_MP = 'MissingPunch'; // MissingPunch
    public const XML_DSQ = 'Disqualified'; // Disqualified
    public const XML_OVER_TIME = 'OverTime'; // OverTime
}
