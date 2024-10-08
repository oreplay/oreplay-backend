<?php

declare(strict_types = 1);

namespace App\Model\Table;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\ORM\Entity;
use Cake\Utility\Text;
use RestApi\Model\Table\RestApiTable;

abstract class AppTable extends RestApiTable
{
    const TABLE_PREFIX = '';


    public function patchFromNewWithUuid(array $data)
    {
        $entity = $this->newEmptyEntity();
        $entity->id = Text::uuid();
        return $this->patchEntity($entity, $data);
    }

    public function patchNewWithStage(array $data, string $eventId, string $stageId)
    {
        $res = $this->patchFromNewWithUuid($data);
        $res->event_id = $eventId;
        $res->stage_id = $stageId;
        $shortName = $data['short_name'] ?? null;
        if ($shortName) {
            list($cacheKey) = $this->getShortNameCacheKey($eventId, $stageId, $shortName);
            Cache::write($cacheKey, $res, CacheGrp::UPLOAD);
        }
        return $res;
    }

    protected function getByShortName(string $eventId, string $stageId, string $shortName): ?Entity
    {
        return $this->getFromCache($this->getShortNameCacheKey($eventId, $stageId, $shortName));
    }

    protected function getShortNameCacheKey(string $eventId, string $stageId, string $shortName): array
    {
        $conditions = [
            $this->_alias . '.event_id' => $eventId,
            $this->_alias . '.stage_id' => $stageId,
            $this->_alias . '.short_name' => $shortName
        ];
        $cacheKey = 'getByShortName_' . $this->_alias . '_' . md5(json_encode($conditions));
        return [$cacheKey, $conditions];
    }

    protected function getFromCache(array $array)
    {
        list($cacheKey, $conditions) = $array;
        $res = Cache::read($cacheKey, CacheGrp::UPLOAD);
        if ($res) {
            return $res;
        }

        $res = $this->find()
            ->where($conditions)
            ->first();
        Cache::write($cacheKey, $res, CacheGrp::UPLOAD);
        return $res;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data)
    {
        $entity = $this->getByShortName($eventId, $stageId, $data['short_name']);
        if (!$entity) {
            $entity = $this->patchNewWithStage($data, $eventId, $stageId);
        }
        return $entity;
    }
}
