<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;

/**
 * Several listeners on one event: a class committing is worth recording in the upload log and worth
 * pushing to whoever is watching, and neither should know about the other.
 */
class UploadPublishers implements UploadPublisher
{
    /**
     * @param UploadPublisher[] $publishers
     */
    public function __construct(private readonly array $publishers)
    {
    }

    public function classImported(string $stageId, ClassImportReport $report): void
    {
        foreach ($this->publishers as $publisher) {
            $publisher->classImported($stageId, $report);
        }
    }
}
