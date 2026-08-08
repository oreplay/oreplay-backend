<?php

declare(strict_types = 1);

namespace Results\Lib;

class UploadContext
{
    private string $_eventId;
    private string $_stageId;
    private string $_classId;

    public function __construct(string $eventId, string $stageId, string $classId = '')
    {
        $this->_eventId = $eventId;
        $this->_stageId = $stageId;
        $this->_classId = $classId;
    }

    public function getEventId(): string
    {
        return $this->_eventId;
    }

    public function getStageId(): string
    {
        return $this->_stageId;
    }

    public function getClassId(): string
    {
        return $this->_classId;
    }

    public function inClass(string $classId): self
    {
        return new self($this->_eventId, $this->_stageId, $classId);
    }
}
