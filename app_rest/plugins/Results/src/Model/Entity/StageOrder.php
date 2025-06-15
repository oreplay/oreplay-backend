<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property int $stage_order
 * @property string $description
 * @property string $event_id
 * @property string $stage_id
 * @property string $original_stage_id
 */
class StageOrder extends AppEntity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
    ];

    protected $_hidden = [
        'event_id',
        'stage_id',
        'original_stage_id',
        'stage_order',
        'created',
        'modified',
        'deleted',
    ];
}
