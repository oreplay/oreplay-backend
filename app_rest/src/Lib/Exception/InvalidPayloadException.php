<?php

declare(strict_types = 1);

namespace App\Lib\Exception;

use RestApi\Lib\Exception\DetailedException;

class InvalidPayloadException extends DetailedException
{
    protected $_defaultCode = 400;
}
