<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $description
 */
class Organizer extends AppEntity
{
    public const ID = '8f3b542c-23b9-4790-a113-b83d476c0ad9';
    public const NAME = 'ADCON';

    protected $_accessible = [
        '*' => false,
        'name' => true,
        'country' => true,
        'region' => true
    ];

    protected $_hidden = [
        'external_id',
        'created',
        'modified',
        'deleted',
    ];
}
