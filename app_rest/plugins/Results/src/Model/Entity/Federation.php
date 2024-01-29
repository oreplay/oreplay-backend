<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class Federation extends Entity
{
    const FEDO = 'FEDO';
    const IOF = 'IOF';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected $_hidden = [
        'deleted',
    ];
}
