<?php

declare(strict_types = 1);

namespace Results\Lib;

use Results\Model\Entity\Control;

interface UploadInterface
{
    public function getEventId(): string;

    public function getStageId(): string;

    public function getExistingControlByStation($stationNumber): ?Control;

    public function storeControlByStation(Control $control): void;
}
