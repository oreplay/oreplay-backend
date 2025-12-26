<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;

/**
 * @property string $token
 * @property string $foreign_key
 * @property string $foreign_model
 * @property FrozenTime $expires
 * @property FrozenTime $deleted
 */
class Token extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'expires' => true,
    ];

    protected array $_virtual = [
    ];

    protected array $_hidden = [
        'id',
        'foreign_model',
        'foreign_key',
        'modified',
        'deleted',
    ];
}
