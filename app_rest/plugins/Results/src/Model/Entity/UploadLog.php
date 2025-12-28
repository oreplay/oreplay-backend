<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Cake\I18n\FrozenTime;
use Results\Lib\Consts\UploadTypes;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string $upload_type
 * @property int $upload_status
 * @property string $info
 * @property int $state
 * @property FrozenTime $created
 */
class UploadLog extends AppEntity
{
    private const STATE_CLEAR = 0;
    public const STATE_START = 1;
    private const STATE_RESULT = 2;
    public const STATE_ENDED = 3;

    protected array $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected array $_hidden = [
        'id',
        'event_id',
        'stage_id',
        'upload_type',
        'upload_status',
        'info',
        'modified',
        'deleted',
    ];

    public function setClearState(): int
    {
        $this->state = self::STATE_CLEAR;
        return $this->state;
    }

    public function setEndedState(): int
    {
        $this->state = self::STATE_ENDED;
        return $this->state;
    }

    public function setUploadState(): int
    {
        if (in_array($this->upload_type, [UploadTypes::START_LIST, UploadTypes::ENTRY_LIST])) {
            $this->state = self::STATE_START;
        } else {
            $this->state = self::STATE_RESULT;
        }
        return $this->state;
    }

    public function toSimpleArray(string $url): array
    {
        $created = $this->created;
        return $this->toChild('SimpleLog', [
            'link_upload' => $url . urlencode($created->toIso8601String()),
            'upload_type' => $this->upload_type,
            'state' => $this->state,
        ]);
    }
}
