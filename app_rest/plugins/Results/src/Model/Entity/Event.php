<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class Event extends Entity
{
    public const FIRST_EVENT = '8f3b542c-23b9-4790-a113-b83d476c0ad9';

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
