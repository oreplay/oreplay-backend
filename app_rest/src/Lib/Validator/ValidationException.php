<?php

namespace App\Lib\Validator;

use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\HttpException;
use Throwable;

class ValidationException extends HttpException
{
    protected $_entity;

    public function __construct(EntityInterface $message = null, ?int $code = 400, ?Throwable $previous = null)
    {
        $this->_entity = $message;
        $message = 'Validation Exception';
        parent::__construct($message, $code, $previous);
    }

    public function getValidationErrors()
    {
        return $this->_entity->getErrors();
    }
}
