<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $description
 */
class Organizer extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'name' => true,
        'country' => true,
        'region' => true
    ];

    protected $_hidden = [
        'created',
        'modified',
        'deleted',
    ];
}
