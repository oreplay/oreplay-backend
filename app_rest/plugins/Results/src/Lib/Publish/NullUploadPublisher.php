<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;

/**
 * Publishes nowhere. The call site exists so that pushing results later is a change of collaborator rather
 * than a change to the import loop.
 */
class NullUploadPublisher implements UploadPublisher
{
    public function classImported(string $stageId, ClassImportReport $report): void
    {
    }
}
