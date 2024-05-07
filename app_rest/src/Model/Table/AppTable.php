<?php

declare(strict_types = 1);

namespace App\Model\Table;

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

    protected function getByShortName(string $eventId, string $stageId, string $shortName): ?Entity
    {
        $conditions = [
            $this->_alias . '.event_id' => $eventId,
            $this->_alias . '.stage_id' => $stageId,
            $this->_alias . '.short_name' => $shortName
        ];
        $cacheKey = 'getByShortName_' . md5(json_encode($conditions));
        $res = Cache::read($cacheKey);
        if ($res) {
            return $res;
        }

        $res = $this->find()
            ->where($conditions)
            ->first();
        Cache::write($cacheKey, $res);
        return $res;
    }

    public function createIfNotExists(string $eventId, string $stageId, array $data)
    {
        $entity = $this->getByShortName($eventId, $stageId, $data['short_name']);
        if (!$entity) {
            $entity = $this->patchFromNewWithUuid($data);
            $entity->event_id = $eventId;
            $entity->stage_id = $stageId;
        }
        return $entity;
    }
}
