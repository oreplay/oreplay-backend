<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string $oe_key
 * @property string $short_name
 * @property string $long_name
 */
class PartialOverall extends RunnerResult
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_hidden = [
    ];

    public function __construct(array $properties = [], array $options = [])
    {
        parent::__construct($properties, $options);
    }

    public static function fromResult(RunnerResult | TeamResult $result): static
    {
        $toRet = new PartialOverall($result->toArray());
        $toRet->stage_order = $result->stage_order;
        return $toRet->setOriginal($result);
    }

    public static function fromValues(
        $stageOrder = null,
        $pos = null,
        $time = null,
        $points = null,
        string $id = '',
        ?StageOrder $stage = null
    ): PartialOverall {
        $overall = new PartialOverall();
        $overall->id = $id;
        $overall->stage_order = $stageOrder;
        $overall->setStage($stage);
        $overall->setPosition($pos);
        $overall->time_seconds = $time;
        $overall->points_final = $points;
        return $overall;
    }

    private function setOriginal(RunnerResult | TeamResult $result): static
    {
        $this->_original = $result;
        return $this;
    }

    public function getPoints(): ?float
    {
        $points = $this->_fields['points_final'] ?? null;
        if ($points !== null) {
            return (float)$points;
        }
        return null;
    }

    private ?StageOrder $_stage = null;

    public function setStage(?StageOrder $stage): static
    {
        $this->_stage = $stage;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'stage_order' => $this->stage_order,
            'stage' => $this->_stage,
            'position' => $this->position,
            'time_seconds' => $this->time_seconds,
            'points_final' => $this->points_final,
        ];
    }

    public function setPosition(?int $position): static
    {
        $this->_fields['position'] = $position;
        return $this;
    }
}
