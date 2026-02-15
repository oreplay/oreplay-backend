<?php

declare(strict_types = 1);

namespace App\Lib\Validator;

use Cake\Datasource\EntityInterface;
use Throwable;

class ValidationException extends \RestApi\Lib\Validator\ValidationException
{
    public function __construct(EntityInterface $message = null, ?int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
