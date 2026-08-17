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

    public const ENVELOPE_KEY = 'oreplay_data_transfer';

    private array $_transfer = [];
    private array $_firstStage = [];

    /**
     * For a JSON upload, whose transfer node is wrapped in the envelope the desktop client sends.
     */
    public static function fromPayload(array $payload): self
    {
        $transfer = $payload[self::ENVELOPE_KEY] ?? null;
        if (!$transfer) {
            throw new InvalidPayloadException(
                'Invalid payload structure ' . self::ENVELOPE_KEY . ' must be root element');
        }
        return new self($transfer);
    }

    /**
     * For a source with no envelope to unwrap — an IOF XML document is a ResultList, not an
     * oreplay_data_transfer. Keeping the key out of everything below this line is the point.
     */
    public static function fromTransfer(array $transfer): self
    {
        return new self($transfer);
    }

    private function __construct(array $transfer)
    {
        $this->_transfer = $transfer;
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
        $currentStageId = $this->_transfer['event']['stages'][0]['id'] ?? null;
        if (!$currentStageId) {
            throw new BadRequestException('Stage id not defined in event.stages.0.id');
        }
        return $table->getStageTypeId($currentStageId) === StageType::TOTALS;
    }

    public function overwriteStageId(string $id): UploadConfigChecker
    {
        if (isset($this->_transfer['event']['stages'][0]['id'])) {
            $this->_transfer['event']['stages'][0]['id'] = $id;
        }
        return $this;
    }

    public function validateStructure(string $eventId): self
    {
        $data = $this->_transfer;
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

    /**
     * iterable, not array, so an XML source can hand back a generator and hold one class in memory at
     * a time. is_iterable() keeps the structural check alive for both shapes.
     */
    public function getClasses(): iterable
    {
        $classes = $this->_firstStage['classes'] ?? null;
        if (!is_iterable($classes)) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.classes');
        }
        return $classes;
    }

    /**
     * The inverse of preCheckType(): given one of our upload types, the vendor words a transfer node has
     * to carry for the truth table below to recognise it again. It lives here so those words stay in one
     * class — a source with no `configuration` block of its own, such as IOF XML, still speaks through
     * this vocabulary rather than copying it.
     */
    public static function configurationFor(string $uploadType): array
    {
        $contents = [
            UploadTypes::START_LIST => [self::LIST_START, self::TYPE_START],
            UploadTypes::ENTRY_LIST => [self::ENTRY_LIST, self::TYPE_START],
            UploadTypes::INTERMEDIATES => [self::LIST_RESULT, self::TYPE_INTERMEDIATES],
            UploadTypes::SPLITS => [self::LIST_RESULT, self::TYPE_SPLITS],
            UploadTypes::FINISH_TIMES => [self::LIST_RESULT, self::TYPE_FINISH_TIMES],
        ][$uploadType] ?? null;
        if (!$contents) {
            throw new InvalidPayloadException('No upload configuration for ' . $uploadType);
        }
        return [
            'contents' => $contents[0],
            'results_type' => $contents[1],
            'totalization' => self::TYPE_START,
        ];
    }

    public function preCheckType(): string
    {
        $contents = $this->_transfer['configuration']['contents'] ?? null;
        $resultsType = $this->_transfer['configuration']['results_type'] ?? null;
        $totalization = $this->_transfer['configuration']['totalization'] ?? null;
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
