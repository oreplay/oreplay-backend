<?php

declare(strict_types = 1);

namespace App\Lib\Validator;

use Cake\Validation\Validator;

class AppValidator extends Validator
{

    public function justLetters(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-letters',
            [
                'rule' => ['custom', '/^[^0-9\\\<\>#&%$?!=]+$/'],
                'message' => $message,
            ]
        );
    }

    public function phone(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-phone',
            [
                'rule' => ['custom', '/^[\+]{0,1}[0-9\ \(\)\/-]{0,60}$/i'],
                'message' => $message,
            ]
        )
            ->minLength($field, 7, $message);
    }

    public function gender(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-gender', [
            'rule' => ['custom', '/^[m f]$/'],
            'message' => $message,
        ]);
    }

    public function iban(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-iban', [
            'rule' => [$this, '_validateIban'],
            'message' => $message
        ]);
    }
    public function _validateIban($value, array $context)
    {
        return Iban::validateIban($value);
    }

    public function bic(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-bic', [
            'rule' => [$this, '_validateBic'],
            'message' => $message
        ]);
    }
    public function _validateBic($value, array $context)
    {
        return Iban::validateBic($value);
    }
}
