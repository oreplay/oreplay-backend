<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\ORM\Entity;

class ResultType extends Entity
{
    public const OVERAL = '5542d38b-8bd3-40f4-913d-2c38048a0b04';
    public const STAGE = 'e4ddfa9d-3347-47e4-9d32-c6c119aeac0e';
    public const TRAIL_NORMAL = '0ca9c166-929e-4b14-a408-28aa4ddeca81';
    public const TRAIL_TIMED = '935acae9-1bad-4a79-9010-018008a6766a';
    public const RAID_SECTION = '9ce3b477-ea6a-409e-8516-3cb4fe85ad31';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected $_hidden = [
        'created',
        'modified',
        'deleted',
    ];
}
