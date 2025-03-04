<?php

declare(strict_types = 1);

namespace Results\Lib;

use App\Lib\Exception\InvalidPayloadException;
use Results\Lib\Consts\UploadTypes;

class UploadConfigChecker
{
    private const LIST_START = 'StartList';
    private const LIST_RESULT = 'ResultList';

    private const TYPE_START = 'Other';
    private const TYPE_INTERMEDIATES = 'Radiocontrols';
    private const TYPE_FINISH_TIMES = 'Totals';
    private const TYPE_SPLITS = 'Breakdown';

    private array $_data;
    private array $_firstStage;

    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    public function isStartLists(): bool
    {
        return $this->preCheckType() === UploadTypes::START_LIST;
    }

    public function isIntermediates(): bool
    {
        return $this->preCheckType() === UploadTypes::INTERMEDIATES;
    }

    public function validateStructure(string $eventId): self
    {
        $data = $this->_getDataTransferred();
        if (!isset($data['event']['id'])) {
            throw new InvalidPayloadException('Invalid payload structure event.id');
        }
        if ($data['event']['id'] !== $eventId) {
            throw new InvalidPayloadException('Event.id must match. Found: ' . $data['event']['id']);
        }

        $this->_firstStage = $data['event']['stages'][0] ?? [];
        $this->getStageId();
        $this->getClasses();
        return $this;
    }

    public function getStageId()
    {
        if (!$this->_firstStage) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0');
        }
        $stageId = $this->_firstStage['id'] ?? null;
        if (!$stageId) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.id');
        }
        return $stageId;
    }

    public function getClasses(): array
    {
        $classes = $this->_firstStage['classes'] ?? null;
        if (!is_array($classes)) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.classes');
        }
        return $classes;
    }

    private function _getDataTransferred()
    {
        $data = $this->_data['oreplay_data_transfer'] ?? null;
        if (!$data) {
            throw new InvalidPayloadException('Invalid payload structure oreplay_data_transfer must be root element');
        }
        return $data;
    }

    public function preCheckType(): string
    {
        $contents = $this->_getDataTransferred()['configuration']['contents'] ?? null;
        $resultsType = $this->_getDataTransferred()['configuration']['results_type'] ?? null;
        $toRet = null;
        if ($contents === self::LIST_START && $resultsType === self::TYPE_START) {
            $toRet = UploadTypes::START_LIST;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_INTERMEDIATES) {
            $toRet = UploadTypes::INTERMEDIATES;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_FINISH_TIMES) {
            $toRet = UploadTypes::FINISH_TIMES;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_SPLITS) {
            $toRet = UploadTypes::SPLITS;
        }
        if ($toRet) {
            return $toRet;
        }
        throw new InvalidPayloadException(
            "Invalid payload structure configuration.contents $contents and configuration.contents $resultsType");
    }
}
