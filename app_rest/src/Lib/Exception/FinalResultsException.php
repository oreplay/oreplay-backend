<?php

declare(strict_types = 1);

namespace App\Lib\Exception;

use RestApi\Lib\Exception\DetailedException;

class FinalResultsException extends DetailedException
{
    protected int $_defaultCode = 409;

    public function __construct()
    {
        parent::__construct('The results of this stage are final, make them provisional before changing them');
    }
}
