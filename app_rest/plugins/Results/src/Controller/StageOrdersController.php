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
    protected function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->_isUserAllowedInStage($eventId, $stageId);

        $this->return = $this->StageOrders->find()
            ->where(['event_id' => $eventId, 'stage_id' => $stageId])
            ->orderByAsc('stage_order')
            ->all()
            ->map(function (StageOrder $stageOrder) {
                return $stageOrder->toArrayManagement();
            })
            ->toList();
    }

    protected function edit($id, $data)
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $this->_isUserAllowedInStage($eventId, $stageId);

        /** @var StageOrder $stageOrder */
        $stageOrder = $this->StageOrders->find()
            ->where(['id' => $id, 'stage_id' => $stageId])
            ->firstOrFail();
        $stageOrder = $this->StageOrders->patchEntity($stageOrder, $data, [
            'accessibleFields' => $this->_editableOriginalIds($stageOrder),
        ]);
        $saved = $this->StageOrders->saveOrFail($stageOrder);
        $this->StageOrders->deleteCache($stageId);
        $this->return = $this->StageOrders->get($saved->id)->toArrayManagement();
    }

    private function _editableOriginalIds(StageOrder $stageOrder): array
    {
        return [
            'original_event_id' => $stageOrder->original_event_id === null,
            'original_stage_id' => $stageOrder->original_stage_id === null,
        ];
    }

    private function _isUserAllowedInStage(string $eventId, string $stageId): void
    {
        $stages = StagesTable::load();
        $userId = $this->OAuthServer->getUserID();
        $stages->Events->getEventFromUser($eventId, $userId);
        $stage = $stages->find()->where(['id' => $stageId, 'event_id' => $eventId])->first();
        if (!$stage) {
            throw new ForbiddenException('The stage is not from this event');
        }
    }
}
