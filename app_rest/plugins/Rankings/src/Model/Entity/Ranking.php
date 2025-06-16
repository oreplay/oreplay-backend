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
    protected $_accessible = [
        '*' => false,
        'id' => false,
    ];

    protected $_hidden = [
        'deleted',
    ];

    public function _getMaxPoints(): float
    {
        return (float)($this->_fields['max_points'] ?? 0.0);
    }

    public function _getRoundPrecision(): int
    {
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
