<?php

declare(strict_types = 1);

namespace App\Lib\Exception;

use Cake\Http\Exception\HttpException;
use Throwable;

class DetailedException extends HttpException
{
    protected $_defaultCode = 400;

    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = namespaceSplit(get_class($this))[1];
        }
        parent::__construct($message, $code, $previous);
    }
}
