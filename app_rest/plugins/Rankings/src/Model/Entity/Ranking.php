<?php

declare(strict_types = 1);

namespace Rankings\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;
use RestApi\Model\Entity\LinkHref;
use Results\Model\Entity\AppEntity;

/**
 * @property string $event_id
 * @property string $stage_id
 * @property string|null $title
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
        'scoring_algorithm' => true,
        'event_id' => true,
        'stage_id' => true,
        'title' => true,
        'max_points' => true,
        'round_precision' => true,
        'nc_true' => true,
        'nc_false' => true,
        'status_scores' => true,
        'excluded_class_names' => true,
        'overall_settings' => true,
    ];

    protected array $_virtual = [
        '_links',
    ];

    protected array $_hidden = [
        'deleted',
    ];

    public function _get_links(): array
    {
        $host = FullBaseUrl::host();
        $self = $host . ApiController::ROUTE_PREFIX . '/rankings/' . $this->id;
        $results = $host . '/competitions/' . $this->event_id . '/' . $this->stage_id;
        return $this->toChild('RankingLinks', [
            'self' => new LinkHref(['href' => $self]),
            'results' => new LinkHref(['href' => $results]),
        ]);
    }

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

    public function getOverallSettings(): ?array
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
