<?php

declare(strict_types = 1);

namespace App\Lib\Exception;

use RestApi\Lib\Exception\DetailedException;

class InvalidTokenException extends DetailedException
{
    protected int $_defaultCode = 403;
}
