<?php

declare(strict_types = 1);

namespace Results\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;

class RawUploadsFixture extends RestApiFixture
{
    public const LOAD = 'plugin.Results.RawUploads';

    public const FIRST = '8b299215-d9ad-4854-9173-342063f9a410';

    public $records = [
        [
            'id' => RawUploadsFixture::FIRST,
            'event_id' => Event::FIRST_EVENT,
            'stage_id' => Stage::FIRST_STAGE,
            'upload_log_id' => UploadLogsFixture::FIRST,
            'file_data' => '{"empty":"fixture"}',
            'created' => '2024-01-02 10:00:05',
            'modified' => '2024-01-02 10:00:05',
            'deleted' => null,
        ]
    ];
}
