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
    protected array $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected array $_hidden = [
    ];

    private array $_parts = [];
    private ?PartialOverall $_overall = null;

    public function setParts(array $parts): static
    {
        $this->_parts = $parts;
        return $this;
    }

    /**
     * @return PartialOverall[]
     */
    public function _getParts(): array
    {
        return $this->_parts;
    }

    public function updateComputedOrganizers(?float $avgPoints, ?int $avgSeconds)
    {
        foreach ($this->_getParts() as $part) {
            if ($part->isComputableOrganizer()) {
                $part->setPoints($avgPoints);
                $part->setTimeSeconds($avgSeconds);
            }
        }
    }

    public function getBestParts(int $maxAmount): array
    {
        $parts = $this->_getParts();
        usort($parts, PartialOverall::sortTotals());
        $toRet = [];
        foreach ($parts as &$part) {
            $isBestPart = count($toRet) < $maxAmount;
            if ($isBestPart) {
                $toRet[] = $part;
            }
            $part->setContributory($isBestPart);
        }
        return $toRet;
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
        return $this->toChild('Overalls', [
            'parts' => $this->_parts,
            'overall' => $this->_overall,
        ]);
    }


    public function setOverallPosition(int $newPosition): void
    {
        $this->_overall->setPosition($newPosition);
    }

    public function hasParts(): bool
    {
        return !empty($this->_parts);
    }

    public function hasOverall(): bool
    {
        return $this->_overall !== null;
    }

    public function isTotallyEmpty(): bool
    {
        return !$this->hasParts() && !$this->hasOverall();
    }
}
