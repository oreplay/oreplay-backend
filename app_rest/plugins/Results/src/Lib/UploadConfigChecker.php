<?php

declare(strict_types = 1);

namespace Results\Lib;

use App\Lib\Exception\InvalidPayloadException;

class UploadConfigChecker
{
    private const LIST_START = 'StartList';
    private const LIST_RESULT = 'ResultList';

    private const TYPE_START = 'Other';
    private const TYPE_INTERMEDIATES = 'Radiocontrols';
    private const TYPE_FINISH_TIMES = 'Totals';
    private const TYPE_SPLITS = 'Breakdown';

    private const START_LIST = 'start_list';
    private const INTERMEDIATES = 'res_intermediates';
    private const FINISH_TIMES = 'res_finish';
    private const SPLITS = 'res_splits';

    private array $_data;
    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    public function isStartLists(): bool
    {
        return $this->preCheckType() === self::START_LIST;
    }

    public function validateStructure(string $eventId): array
    {
        $data = $this->_getDataTransferred();
        if (!isset($data['event']['id'])) {
            throw new InvalidPayloadException('Invalid payload structure event.id');
        }
        if ($data['event']['id'] !== $eventId) {
            throw new InvalidPayloadException('Event id must match');
        }

        $firstStage = $data['event']['stages'][0] ?? null;
        if ($firstStage) {
            $data = $firstStage;
        } else {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0');
        }
        $stageId = $firstStage['id'] ?? null;
        if (!$stageId) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.id');
        }
        $data = $this->_validateClasses($data);
        return [$data, $stageId];
        return $this;
    }

    private function _validateClasses($data): array
    {
        $data = $data['classes'] ?? null;
        if (!is_array($data)) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.classes');
        }
        return $data;
    }

    private function _getDataTransferred()
    {
        $data = $this->_data['oreplay_data_transfer'] ?? null;
        if (!$data) {
            throw new InvalidPayloadException('Invalid payload structure oreplay_data_transfer must be root element');
        }
        return $data;
    }

    private function preCheckType(): string
    {
        $contents = $this->_getDataTransferred()['configuration']['contents'] ?? null;
        $resultsType = $this->_getDataTransferred()['configuration']['results_type'] ?? null;
        if ($contents === self::LIST_START && $resultsType === self::TYPE_START) {
            return self::START_LIST;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_INTERMEDIATES) {
            return self::INTERMEDIATES;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_FINISH_TIMES) {
            return self::FINISH_TIMES;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_SPLITS) {
            return self::SPLITS;
        }
        throw new InvalidPayloadException(
            "Invalid payload structure configuration.contents $contents and configuration.contents $resultsType");
    }
}
