<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\Consts\UploadTypes;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string $upload_type
 * @property int $upload_status
 * @property string $info
 * @property int $state
 */
class UploadLog extends AppEntity
{
    private const STATE_CLEAR = 0;
    public const STATE_START = 1;
    private const STATE_RESULT = 2;
    private const STATE_ENDED = 3;

    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_hidden = [
    ];

    public function setClearState(): int
    {
        $this->state = self::STATE_CLEAR;
        return $this->state;
    }

    public function setUploadState(): int
    {
        if ($this->upload_type === UploadTypes::START_LIST) {
            $this->state = self::STATE_START;
        } else {
            $this->state = self::STATE_RESULT;
        }
        return $this->state;
    }
}
