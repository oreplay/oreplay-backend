<?php

declare(strict_types = 1);

namespace Results\Model\Table;

use App\Lib\Consts\CacheGrp;
use App\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\ORM\Behavior\TimestampBehavior;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\UploadHelper;
use Results\Model\Entity\Club;

/**
 * @property RunnersTable $Runner
 */
class ClubsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
        RunnersTable::addBelongsTo($this);
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data): Club
    {
        /** @var Club $club */
        $oeKey = $data['oe_key'] ?? null;
        if ($oeKey) {
            $club = $this->getByOeKey($eventId, $stageId, $oeKey);
        } else {
            if (!($data['short_name'] ?? null)) {
                $data['short_name'] = $data['long_name'] ?? '';
            }
            if (!$data['short_name']) {
                throw new DetailedException('Clubs must have short_name or long_name or oe_key for identification');
            }
            $club = $this->getByShortName($eventId, $stageId, $data['short_name']);
        }
        if (!$club) {
            $club = $this->fillNewWithStage($data, $eventId, $stageId);
            if ($oeKey) {
                list($cacheKey) = $this->getOeKeyCacheKey($eventId, $stageId, $oeKey);
                Cache::write($cacheKey, $club, CacheGrp::UPLOAD);
            }
        }
        return $club;
    }

    public function findByStage(string $eventId, string $stageId)
    {
        return $this->find()->where([
            'event_id' => $eventId,
            'stage_id' => $stageId,
        ])
            ->order(['short_name' => 'ASC']);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId)
    {
        /** @var Club $res */
        $res = parent::fillNewWithStage($data, $eventId, $stageId);
        if (!$res->short_name && $res->long_name) {
            $res->short_name = $res->long_name;
        }
        return $res;
    }

    protected function getOeKeyCacheKey(string $eventId, string $stageId, string $oeKey): array
    {
        $conditions = [
            $this->_alias . '.event_id' => $eventId,
            $this->_alias . '.stage_id' => $stageId,
            $this->_alias . '.oe_key' => $oeKey
        ];
        $cacheKey = 'getByOeKey_' . UploadHelper::md5Encode($conditions);
        return [$cacheKey, $conditions];
    }

    protected function getByOeKey(string $eventId, string $stageId, string $oeKey): ?Club
    {
        return $this->getFromCache($this->getOeKeyCacheKey($eventId, $stageId, $oeKey));
    }
}
