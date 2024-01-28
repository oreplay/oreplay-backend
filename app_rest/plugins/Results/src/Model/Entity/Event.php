<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Event extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'initial_date' => true,
        'final_date' => true,
    ];

    protected $_hidden = [
        'deleted',
    ];
}
