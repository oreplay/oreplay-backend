<?php

declare(strict_types = 1);

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * @property string firstname
 * @property string lastname
 * @property string email
 * @property mixed group_id
 * @property mixed $password
 */
class User extends Entity
{
    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);
    }

    protected $_accessible = [
        '*' => false,
        'id' => false,

        'password' => true,
        'email' => true,
        'first_name' => true,
        'last_name' => true,
    ];

    protected $_hidden = [
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
}
