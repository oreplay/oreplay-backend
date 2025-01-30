<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;

/**
 * @property string $event_id
 * @property integer $stage_type_id
 * @property StageType $stage_type
 * @property string $description
 */
class Stage extends AppEntity
{
    public const FIRST_STAGE = '51d63e99-5d7c-4382-a541-8567015d8eed';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'stage_type_id' => true,
    ];

    protected $_virtual = [
        '_links',
    ];

    protected $_hidden = [
        'event_id',
        'base_date',
        'base_time',
        'order_number',
        'stage_type_id',
        'server_offset',
        'utc_value',
        'created',
        'modified',
        'deleted',
    ];

    public function _get_links(): array
    {
        $resultsPath = $this->isTeam() ? '/results/' : '/runners/';
        return [
            'self' => $this->_path() . $this->id,
            'results' => $this->_path() . $this->id . $resultsPath,
            'classes' => $this->_path() . $this->id . '/classes/'
        ];
    }

    private function _path()
    {
        return FullBaseUrl::host() . ApiController::ROUTE_PREFIX . '/events/' . $this->event_id . '/stages/';
    }

    private function isTeam(): bool
    {
        return in_array($this->stage_type_id, [StageType::RAID]);
    }
}
