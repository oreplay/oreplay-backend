<?php

declare(strict_types = 1);

namespace Results\Controller;

use Cake\Http\Exception\ForbiddenException;
use Results\Model\Entity\StageOrder;
use Results\Model\Table\StagesTable;

/**
 * @property \Results\Model\Table\StageOrdersTable $StageOrders
 */
class StageOrdersController extends ApiController
{
    protected function edit($id, $data)
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->_isUserAllowedInStage($eventId, $stageId);

        /** @var StageOrder $stageOrder */
        $stageOrder = $this->StageOrders->find()
            ->where(['id' => $id, 'stage_id' => $stageId])
            ->firstOrFail();
        // only `description` is accessible in the entity, any other field is ignored
        $stageOrder = $this->StageOrders->patchEntity($stageOrder, $data);
        $saved = $this->StageOrders->saveOrFail($stageOrder);
        $this->StageOrders->deleteCache($stageId);
        $this->return = $this->StageOrders->get($saved->id);
    }

    private function _isUserAllowedInStage(string $eventId, string $stageId): void
    {
        $stages = StagesTable::load();
        $userId = $this->getLocalOauth()->verifyAuthorizationAndGetToken()->getUserId();
        $stages->Events->getEventFromUser($eventId, $userId);
        $stage = $stages->find()->where(['id' => $stageId, 'event_id' => $eventId])->first();
        if (!$stage) {
            throw new ForbiddenException('The stage is not from this event');
        }
    }
}
