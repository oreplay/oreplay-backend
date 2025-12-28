<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use App\Controller\ApiController;
use App\Lib\FullBaseUrl;
use Cake\I18n\FrozenTime;
use RestApi\Model\Entity\LinkHref;

/**
 * @property string $event_id
 * @property string $stage_type_id
 * @property StageType $stage_type
 * @property string $description
 * @property FrozenTime $created
 */
class Stage extends AppEntity
{
    public const FIRST_STAGE = '51d63e99-5d7c-4382-a541-8567015d8eed';

    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'stage_type_id' => true,
    ];

    protected array $_virtual = [
        'last_logs',
        '_links',
    ];

    protected array $_hidden = [
        'event_id',
        'base_date',
        'base_time',
        'order_number',
        'stage_type_id',
        'server_offset',
        'utc_value',
        'upload_logs',
        'created',
        'modified',
        'deleted',
    ];

    /**
     * @return UploadLog[]
     */
    public function _getLastLogs(): array
    {
        $toRet = [];
        if (!$this->upload_logs) {
            return [];
        }
        /** @var UploadLog $log */
        foreach ($this->upload_logs as $log) {
            $toRet[$log->state] = $log;
        }
        return array_values($toRet);
    }

    public function _get_links(): array
    {
        return $this->toChild('StageLinks', [
            'self' => new LinkHref(['href' => $this->_path() . $this->id]),
            'results' => new LinkHref(['href' => $this->_path() . $this->id . '/results/']),
            'classes' => new LinkHref(['href' => $this->_path() . $this->id . '/classes/'])
        ]);
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
