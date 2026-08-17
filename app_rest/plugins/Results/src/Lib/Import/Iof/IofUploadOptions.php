<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

/**
 * What the request says about an IOF upload beyond the document itself. An IOF document names neither the
 * stage it belongs to nor the time zone its times are in, and for some producers it cannot say whether it
 * is a radiocontrol export either.
 */
class IofUploadOptions
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $stageId,
        public readonly ?string $timeZoneName = null,
        public readonly ?string $uploadType = null
    ) {
    }
}
