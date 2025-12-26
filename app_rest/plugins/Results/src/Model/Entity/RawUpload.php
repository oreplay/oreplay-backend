<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $file_data
 * @property string $upload_log_id
 * @property string $event_id
 * @property string $stage_id
 */
class RawUpload extends AppEntity
{
    protected array $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected array $_hidden = [
    ];

    public function getDataAsArray(): array
    {
        return json_decode($this->file_data, true);
    }
}
