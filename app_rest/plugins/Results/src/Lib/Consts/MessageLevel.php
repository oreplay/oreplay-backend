<?php

declare(strict_types = 1);

namespace Results\Lib\Consts;

/**
 * How good the outcome was, which is not the same question as whether the request worked: an upload can
 * answer 200 and still report that rows were lost, and that is an error about the data.
 */
class MessageLevel
{
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';
}
