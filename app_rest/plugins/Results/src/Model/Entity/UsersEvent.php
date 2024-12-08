<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;

/**
 * @property string $user_id
 * @property string $event_id
 * @property FrozenTime $created
 * @property FrozenTime $modified
 */
class UsersEvent extends AppEntity
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
