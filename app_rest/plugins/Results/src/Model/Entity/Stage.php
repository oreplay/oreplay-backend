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
 * @property string|null $external_url
 * @property FrozenTime $created
 */
class Stage extends AppEntity
{
    public const FIRST_STAGE = '51d63e99-5d7c-4382-a541-8567015d8eed';

    private const array PROPERTIES_THE_DESKTOP_CLIENT_DECLARES = [
        '_links',
        'answers',
        'base_date',
        'base_time',
        'classes',
        'classes_controls',
        'clubs',
        'controls',
        'courses',
        'description',
        'event',
        'id',
        'last_logs',
        'order_number',
        'runner_results',
        'runners',
        'server_offset',
        'splits',
        'stage_discipline',
        'stage_type',
        'start',
        'team_results',
        'teams',
        'utc_value',
    ];

    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'description' => true,
        'stage_type_id' => true,
        'start' => true,
        'external_url' => true,
    ];

    protected array $_virtual = [
        'last_logs',
        '_links',
    ];

    protected array $_hidden = [
        'event_id',
        'order_number',
        'stage_type_id',
        'upload_logs',
        'created',
        'modified',
        'deleted',
    ];

    public function shapedForTheDesktopClient(): static
    {
        $this->setHidden(array_diff($this->getVisible(), self::PROPERTIES_THE_DESKTOP_CLIENT_DECLARES), true);
        return $this;
    }

    /**
     * @return UploadLog[]
     */
    public function _getLastLogs(): array
    {
        return UploadLog::onePerState($this->upload_logs);
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
