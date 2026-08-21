<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use Results\Lib\Import\ClassImportReport;
use Results\Lib\Import\UploadProgress;
use Results\Model\Entity\UploadLog;
use Results\Model\Table\UploadLogsTable;

/**
 * Keeps the upload_logs row up to date as classes commit, so an upload killed by a timeout or an out of
 * memory still says where it stopped. The row's modified time doubles as a heartbeat: one that stops
 * advancing is an upload that is stuck rather than slow.
 *
 * v2 only. v1 keeps writing a single row once the upload is over, see docs/uploads-v1-vs-v2.md.
 */
class UploadProgressPublisher implements UploadPublisher
{
    private const MAX_INFO_LENGTH = 255;

    private UploadProgress $_progress;

    public function __construct(private readonly UploadLog $log)
    {
        $this->_progress = new UploadProgress();
    }

    public function classImported(string $stageId, ClassImportReport $report): void
    {
        $this->_progress->add($report);
        $this->log->info = mb_substr($this->_progress->describe(), 0, self::MAX_INFO_LENGTH);
        UploadLogsTable::load()->saveOrFail($this->log);
    }
}
