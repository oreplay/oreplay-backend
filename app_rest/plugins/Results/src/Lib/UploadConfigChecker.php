<?php

declare(strict_types = 1);

namespace Results\Lib;

use App\Lib\Exception\InvalidPayloadException;
use Cake\Http\Exception\BadRequestException;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\StageType;
use Results\Model\Table\StagesTable;

class UploadConfigChecker
{
    private const ENTRY_LIST = 'EntryList';
    private const LIST_START = 'StartList';
    private const LIST_RESULT = 'ResultList';

    private const TYPE_START = 'Other';
    private const TYPE_INTERMEDIATES = 'Radiocontrols';
    private const TYPE_FINISH_TIMES = 'Totals';
    private const TYPE_SPLITS = 'Breakdown';
    private const TYPE_TOTAL_POINTS = 'TotalizationPoints';
    private const TYPE_TOTAL_TIMES = 'TotalizationTime';
    public const TYPE_MIXED = 'Mixed';

    private array $_data;
    private array $_firstStage;

    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    public function isStartLists(): bool
    {
        return in_array($this->preCheckType(), [UploadTypes::START_LIST, UploadTypes::ENTRY_LIST]);
    }

    public function isTotals(): bool
    {
        return in_array($this->preCheckType(), [UploadTypes::TOTAL_POINTS, UploadTypes::TOTAL_TIMES]);
    }

    public function isIntermediates(): bool
    {
        return $this->preCheckType() === UploadTypes::INTERMEDIATES;
    }

    public function isStageTotals(StagesTable $table)
    {
        $currentStageId = $this->_getDataTransferred()['event']['stages'][0]['id'] ?? null;
        if (!$currentStageId) {
            throw new BadRequestException('Stage id not defined in event.stages.0.id');
        }
        return $table->getStageTypeId($currentStageId) === StageType::TOTALS;
    }

    public function overwriteStageId(string $id): UploadConfigChecker
    {
        if (isset($this->_getDataTransferred()['event']['stages'][0]['id'])) {
            $this->_data['oreplay_data_transfer']['event']['stages'][0]['id'] = $id;
        }
        return $this;
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
        $totalization = $this->_getDataTransferred()['configuration']['totalization'] ?? null;
        $toRet = null;
        if ($contents === self::LIST_START && in_array($resultsType, [self::TYPE_START, self::TYPE_MIXED])) {
            $toRet = UploadTypes::START_LIST;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_INTERMEDIATES) {
            $toRet = UploadTypes::INTERMEDIATES;
        }
        if ($contents === self::LIST_RESULT && in_array($resultsType, [self::TYPE_FINISH_TIMES, self::TYPE_MIXED])) {
            $toRet = UploadTypes::FINISH_TIMES;
        }
        if ($contents === self::LIST_RESULT && $resultsType === self::TYPE_SPLITS) {
            $toRet = UploadTypes::SPLITS;
        }
        if ($totalization === self::TYPE_TOTAL_POINTS) {
            $toRet = UploadTypes::TOTAL_POINTS;
        }
        if ($totalization === self::TYPE_TOTAL_TIMES) {
            $toRet = UploadTypes::TOTAL_TIMES;
        }
        if ($contents === self::ENTRY_LIST && $resultsType === self::TYPE_START) {
            $toRet = UploadTypes::ENTRY_LIST;
        }
        if ($toRet) {
            return $toRet;
        }
        throw new InvalidPayloadException(
            "Invalid payload structure configuration.contents $contents and configuration.results_type $resultsType");
    }
}
