<?php

declare(strict_types = 1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use RestApi\Model\Entity\RestApiEntity;

/**
 * @property string firstname
 * @property string lastname
 * @property string email
 * @property mixed group_id
 * @property mixed $password
 * @property mixed $is_admin
 */
class User extends RestApiEntity
{
    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);
    }

    protected array $_accessible = [
        '*' => false,
        'id' => false,

        'password' => true,
        'email' => true,
        'first_name' => true,
        'last_name' => true,
    ];

    protected array $_hidden = [
        'is_admin',
        'is_super',
        'deleted',
        'password',
    ];

    protected function _setPassword($password)
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher)->hash($password);
        }
    }

    public function isManager(): bool
    {
        return (bool)$this->is_admin;
    }
}
