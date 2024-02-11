<?php

namespace Results\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;
use Cake\ORM\Entity;

/**
 * @property string $event_id
 * @property integer $stage_type_id
 */
class Stage extends Entity
{
    public const FIRST_STAGE = '51d63e99-5d7c-4382-a541-8567015d8eed';

    protected $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
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
        'deleted',
    ];

    public function _get_links(): array
    {
        $resultsPath = $this->isTeam() ? '/teams/' : '/runners/';
        return [
            'results' => FullBaseUrl::host() . ApiController::ROUTE_PREFIX
                . '/events/' . $this->event_id . '/stages/' . $this->id . $resultsPath
        ];
    }

    private function isTeam(): bool
    {
        return in_array($this->stage_type_id, [StageType::RAID, StageType::ROGAINE]);
    }
}
