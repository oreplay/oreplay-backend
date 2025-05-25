<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\UploadLogsTable;

class UploadLogsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.UploadLogs';

    public const FIRST = 'f3414e0b-e605-494d-89f0-85d0bfbab2a0';

    public $records = [
        [
            'id' => UploadLogsFixture::FIRST,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'upload_type' => UploadTypes::START_LIST,
            'state' => UploadLog::STATE_START,
            'upload_status' => UploadLogsTable::STATUS_OK,
            'info' => null,
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:09',
            'deleted' => null,
        ]
    ];
}
