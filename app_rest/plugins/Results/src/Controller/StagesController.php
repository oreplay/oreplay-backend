<?php

declare(strict_types = 1);

namespace Results\Controller;

use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Table\StagesTable;

/**
 * @property StagesTable $Stages
 */
class StagesController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    public function getList()
    {
        $eventId = $this->request->getParam('eventID');
        $this->return = $this->Stages->find()
            ->where(['event_id' => $eventId])
            ->orderAsc('created')->all();
    }

    protected function getData($id)
    {
        $eventId = $this->request->getParam('eventID');
        $this->return = $this->Stages->getByEvent($id, $eventId);
    }

    protected function addNew($data)
    {
        $eventId = $this->request->getParam('eventID');
        $userId = $this->getLocalOauth()->verifyAuthorization();
        /** @var Stage $stage */
        $this->Stages->Events->getEventFromUser($eventId, $userId);
        $stage = $this->Stages->patchFromNewWithUuid($data);
        $stage->event_id = $eventId;
        if ($data['stage_type_id'] ?? null) {
            $stageType = $data['stage_type_id'];
        } else {
            $stageType = StageType::CLASSIC;
        }
        $stage->stage_type = $this->Stages->StageTypes->get($stageType);
        $this->return = $this->Stages->saveOrFail($stage);
    }
}
