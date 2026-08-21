<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;

/**
 * Told about each class as it is committed. The implementation that pushes to subscribers needs nothing
 * from the import beyond this, so the import never learns how results are delivered.
 */
interface UploadPublisher
{
    public function classImported(string $stageId, ClassImportReport $report): void;
}
