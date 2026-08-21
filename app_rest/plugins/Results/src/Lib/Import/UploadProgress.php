<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

/**
 * The running total of an upload, so one that is still working — or that died halfway — can say how far it
 * got. There is no percentage because the number of classes is not known in advance: an XML upload streams
 * them one at a time, and counting them first would mean holding the whole document.
 */
class UploadProgress
{
    private int $_classCount = 0;
    private int $_participantCount = 0;
    private string $_lastClassName = '';

    public function add(ClassImportReport $report): void
    {
        $this->_classCount++;
        $this->_participantCount += $report->participantCount();
        $this->_lastClassName = $report->shortName;
    }

    public function classCount(): int
    {
        return $this->_classCount;
    }

    public function participantCount(): int
    {
        return $this->_participantCount;
    }

    public function describe(): string
    {
        if (!$this->_classCount) {
            return 'no class needed importing';
        }
        return $this->_classCount . ' classes, ' . $this->_participantCount
            . ' participants, last ' . $this->_lastClassName;
    }
}
