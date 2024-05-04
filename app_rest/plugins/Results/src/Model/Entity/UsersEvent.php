<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property string $user_id
 * @property string $event_id
 * @property FrozenTime $created
 * @property FrozenTime $modified
 */
class UsersEvent extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_virtual = [
    ];

    protected $_hidden = [
        'deleted',
    ];
}
