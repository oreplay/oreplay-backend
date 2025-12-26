<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $description
 */
class Federation extends AppEntity
{
    public const FEDO = 'FEDO';
    public const IOF = 'IOF';

    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected array $_hidden = [
        'created',
        'modified',
        'deleted',
    ];
}
