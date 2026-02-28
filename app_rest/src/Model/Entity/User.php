<?php

declare(strict_types = 1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use RestApi\Model\Entity\RestApiEntity;
use function Cake\I18n\__;

/**
 * @property string first_name
 * @property string last_name
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
            $isAlreadyHashed = str_starts_with($password, '$2y$10$');
            if ($isAlreadyHashed) {
                return $password;
            }
            return (new DefaultPasswordHasher)->hash($password);
        }
    }

    public function isManager(): bool
    {
        return (bool)$this->is_admin;
    }

    public function setErrorDuplicatedEmail(): void
    {
        $this->setError('email', ['duplicate' => __('email already registered')]);
    }
}
