<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use Cake\Http\Exception\ForbiddenException;
use Rankings\Model\Entity\RankingOrganizer;
use Rankings\Model\Table\RankingOrganizersTable;
use Rankings\Model\Table\RankingsTable;
use Results\Controller\ApiController;
use Results\Model\Entity\StageOrder;
use Results\Model\Table\EventsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StageOrdersTable;

class RankingOrganizersController extends ApiController
{
    private RankingOrganizersTable $RankingOrganizers;
    private StageOrdersTable $StageOrders;
    private EventsTable $Events;
    private RankingsTable $Rankings;

    public function initialize(): void
    {
        parent::initialize();
        $this->RankingOrganizers = RankingOrganizersTable::load();
        $this->StageOrders = StageOrdersTable::load();
        $this->Events = EventsTable::load();
        $this->Rankings = RankingsTable::load();
    }

    protected function getList()
    {
        $stageOrder = $this->_authorizeAndGetStageOrder();
        $this->return = $this->RankingOrganizers->find()
            ->where([RankingOrganizersTable::field('stage_order_id') => $stageOrder->id])
            ->all()
            ->toList();
    }

    protected function addNew($data)
    {
        $stageOrder = $this->_authorizeAndGetStageOrder();
        $runnerId = $data['runner_id'] ?? null;
        if ($runnerId) {
            $this->_assertRunnerExists($runnerId);
        }
        /** @var RankingOrganizer $organizer */
        $organizer = $this->RankingOrganizers->patchFromNewWithUuid([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'description' => $data['description'] ?? null,
            'runner_id' => $runnerId,
            // Present for validation only; stage_order_id is not accessible so it is set below.
            'stage_order_id' => $stageOrder->id,
        ]);
        $organizer->stage_order_id = $stageOrder->id;
        $organizer->setDirty('stage_order_id');
        $this->_assertUnderOrganizerLimit($organizer, $stageOrder);
        $this->return = $this->RankingOrganizers->saveOrFail($organizer);
    }

    protected function delete($id)
    {
        $stageOrder = $this->_authorizeAndGetStageOrder();
        $this->RankingOrganizers->find()
            ->where([
                RankingOrganizersTable::field('id') => $id,
                RankingOrganizersTable::field('stage_order_id') => $stageOrder->id,
            ])
            ->firstOrFail();
        $this->RankingOrganizers->softDelete($id);
        $this->return = false;
    }

    private function _authorizeAndGetStageOrder(): StageOrder
    {
        $rankingId = $this->request->getParam('rankingID');
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        $stageOrderId = $this->request->getParam('stageOrderID');
        $this->Events->getEventFromUser($eventId, $this->OAuthServer->getUserID());
        $ranking = $this->Rankings->getCached($rankingId);
        if ($ranking->getEventId() !== $eventId || $ranking->getStageId() !== $stageId) {
            throw new ForbiddenException('Invalid rankingID for this event and stage');
        }
        /** @var StageOrder $stageOrder */
        $stageOrder = $this->StageOrders->find()
            ->where(['id' => $stageOrderId, 'event_id' => $eventId, 'stage_id' => $stageId])
            ->first();
        if (!$stageOrder) {
            throw new ForbiddenException('The stage order is not from this event and stage');
        }
        return $stageOrder;
    }

    private function _assertUnderOrganizerLimit(RankingOrganizer $newOrganizer, StageOrder $stageOrder): void
    {
        $existing = $this->RankingOrganizers->find()
            ->where([RankingOrganizersTable::field('stage_order_id') => $stageOrder->id])
            ->all()
            ->toList();
        $calculator = RankingsTable::getCalculator($this->request->getParam('rankingID'));
        $calculator->limitOrganizers($newOrganizer, $existing);
    }

    private function _assertRunnerExists(string $runnerId): void
    {
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');
        RunnersTable::load()->assertRunnerExists($runnerId, $eventId, $stageId);
    }
}
