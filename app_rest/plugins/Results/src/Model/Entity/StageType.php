<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $description
 */
class StageType extends AppEntity
{
    public const CLASSIC = '29d5050b-4769-4be5-ace4-7e5973f68e3c';
    public const MASS_START = 'ce5e95ea-9f2b-4a98-86e1-2b43651adfee';
    public const CHASE_START = '080f7e57-9525-4b9a-95ee-b2113f411afd';
    public const RELAY = '9a918410-6dda-4c58-bec9-23839b336e59';
    public const ROGAINE = '2b5de3d0-9bc9-435a-8bd9-2d4060b86e45';
    public const RAID = 'a30b2db1-5649-491a-b5a8-ca53e4e58461';
    public const TRAIL = 'de0dd0e7-ffcb-4bdf-9842-327b4ea33e44';

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
