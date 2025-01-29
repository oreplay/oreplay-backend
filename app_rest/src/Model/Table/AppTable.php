<?php

declare(strict_types = 1);

namespace App\Model\Table;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;
use RestApi\Lib\Exception\DetailedException;
use RestApi\Model\Table\RestApiTable;
use Results\Lib\UploadHelper;
use Results\Model\Entity\AppEntity;

abstract class AppTable extends RestApiTable
{
    const TABLE_PREFIX = '';

    public function patchFromNewWithUuid(array $data)
    {
        $entity = $this->newEmptyEntity();
        $newId = $data['id'] ?? null;
        if ($newId) {
            $entity->id = $newId;
        } else {
            $entity->id = Text::uuid();
        }
        if (!$this->isValidUUID($entity->id)) {
            throw new DetailedException('ID must be in UUID format ISO 9834 or not provided');
        }
        return $this->patchEntity($entity, $data);
    }

    private function isValidUUID(string $uuid): bool
    {
        $regex = '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i';
        return 1 === preg_match($regex, $uuid);
    }

    public function fillNewWithUuid(array $data)
    {
        /** @var AppEntity $entity */
        $entity = $this->newEmptyEntity();
        $entity->id = Text::uuid();
        $schema = $this->getSchema();
        $timezone = $this->getConnection()->config()['timezone'] ?? 'UTC';
        return $entity->fastPatch($data, $schema, $timezone);
    }

    public function findWhereEventAndStage(UploadHelper $helper): Query
    {
        return $this->find()->where([
            'event_id' => $helper->getEventId(),
            'stage_id' => $helper->getStageId()
        ]);
    }

    public function fillNewWithStage(array $data, string $eventId, string $stageId)
    {
        $res = $this->fillNewWithUuid($data);
        $res->event_id = $eventId;
        $res->stage_id = $stageId;
        $res->setDirty('event_id');
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
        // NOSONAR
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
        if (!($data['short_name'] ?? null)) {
            $data['short_name'] = $data['long_name'] ?? '';
        }
        $entity = $this->getByShortName($eventId, $stageId, $data['short_name'] ?? '');
        if (!$entity) {
            $entity = $this->fillNewWithStage($data, $eventId, $stageId);
        }
        return $entity;
    }
}
