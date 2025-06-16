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
class Overalls extends AppEntity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_hidden = [
    ];

    private array $_parts;
    private PartialOverall $_overall;

    public function setParts(array $parts): static
    {
        $this->_parts = $parts;
        return $this;
    }

    public function _getParts(): array
    {
        return $this->_parts;
    }

    public function setOverall(PartialOverall $overall): static
    {
        $this->_overall = $overall;
        return $this;
    }

    public function _getOverall(): PartialOverall
    {
        return $this->_overall;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'parts' => $this->_parts,
            'overall' => $this->_overall,
        ];
    }


    public function setOverallPosition(int $newPosition): void
    {
        $this->_overall->setPosition($newPosition);
    }

    public function hasParts(): bool
    {
        return !empty($this->_parts);
    }
}
