<?php

declare(strict_types = 1);

namespace Rankings\Model\Entity;

use Results\Model\Entity\AppEntity;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property mixed $max_points
 * @property int $round_precision
 * @property mixed $nc_true
 * @property mixed $nc_false
 * @property string|null $status_scores
 * @property string|null $excluded_class_names
 */
class Ranking extends AppEntity
{
    public const int USE_FLOOR_INSTEAD_OF_ROUND = -1;

    protected array $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected array $_hidden = [
        'deleted',
    ];

    public function _getMaxPoints(): float
    {
        return (float)($this->_fields['max_points'] ?? 0.0);
    }

    public function isFloorInsteadOfRound(): bool
    {
        return $this->_getRoundPrecision() === self::USE_FLOOR_INSTEAD_OF_ROUND;
    }

    public function _getRoundPrecision(): int
    {
        // keep in mind self::USE_FLOOR_INSTEAD_OF_ROUND
        return $this->_fields['round_precision'] ?? 0;
    }

    public function _getNcScore(bool $isNc): ?float
    {
        if ($isNc) {
            return $this->nc_true ? (float)$this->nc_true : null;
        }
        return $this->nc_false ? (float)$this->nc_false : null;
    }

    public function getStatusScore(string $status): ?float
    {
        $settings = json_decode((string)$this->status_scores);
        return $settings[$status] ?? null;
    }

    public function _getOverallSettings(): ?array
    {
        $s = $this->_fields['overall_settings'] ?? null;
        if (!$s) {
            return null;
        }
        return json_decode($s, true);
    }

    public function getEventId(): string
    {
        return $this->event_id;
    }

    public function getStageId(): string
    {
        return $this->stage_id;
    }

    public function getExcludedClassNames(): array
    {
        return json_decode($this->excluded_class_names);
    }
}
