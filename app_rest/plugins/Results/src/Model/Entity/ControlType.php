<?php

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class ControlType extends Entity
{
    public const NORMAL = 'f3cc5efa-065f-4ad6-844b-74e99612889b';
    public const START = '5570a504-4803-434a-a3b9-44d24e40c30b';
    public const FINISH = '670d4407-edeb-4062-85d8-f8f31272096f';
    public const CLEAR = 'b62d2a14-6896-4371-80be-55db2160542b';
    public const CHECK = '7b4b9e47-36ed-4b04-8345-0078bbcd7872';

    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_hidden = [
        'created',
        'modified',
        'deleted',
    ];
}
