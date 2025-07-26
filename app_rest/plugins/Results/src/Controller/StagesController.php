<?php

declare(strict_types = 1);

namespace Results\Controller;

use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use Results\Model\Entity\Stage;
use Results\Model\Entity\StageType;
use Results\Model\Table\AnswersTable;
use Results\Model\Table\ClassesControlsTable;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;
use Results\Model\Table\StageOrdersTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;
use Results\Model\Table\UploadLogsTable;

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
        $this->_isUserAllowedInEvent($eventId);
        /** @var Stage $stage */
        $stage = $this->Stages->patchFromNewWithUuid($data);
        $stage->event_id = $eventId;
        if ($data['stage_type_id'] ?? null) {
            $stageType = $data['stage_type_id'];
        } else {
            $stageType = StageType::CLASSIC;
        }
        RawUploadsTable::load()->hardDeleteOld();
        $stage->stage_type = $this->Stages->StageTypes->get($stageType);
        $this->return = $this->Stages->saveOrFail($stage);
    }

    protected function delete($id)
    {
        $eventId = $this->request->getParam('eventID');
        $this->_isUserAllowedInStage($eventId, $id);

        $clean = $this->getRequest()->getQuery('clean');

        $tables = [
            ClubsTable::load(),
            CoursesTable::load(),
            ClassesTable::load(),
            TeamsTable::load(),
            RunnersTable::load(),
            ControlsTable::load(),
            ClassesControlsTable::load(),
            RunnerResultsTable::load(),
            TeamResultsTable::load(),
            SplitsTable::load(),
            AnswersTable::load(),
            StageOrdersTable::load(),
        ];
        $now = new FrozenTime();
        foreach ($tables as $table) {
            $table->updateAll(['deleted' => $now], ['stage_id' => $id, 'deleted is null']);
        }

        if (!$clean) {
            $this->Stages->updateAll(['deleted' => $now], ['id' => $id, 'deleted is null']);
        }
        $this->return = false;
        UploadLogsTable::load()->saveClearLog($eventId, $id);
    }

    protected function edit($id, $data)
    {
        $eventId = $this->request->getParam('eventID');
        $this->_isUserAllowedInStage($eventId, $id);

        $stage =$this->Stages->get($id);
        $stage = $this->Stages->patchEntity($stage, $data);
        $saved = $this->Stages->saveOrFail($stage);
        if (isset($data['state_end'])) {
            if ($data['state_end']) {
                $this->Stages->UploadLogs->saveStateEnded($eventId, $id);
            } else {
                $this->Stages->UploadLogs->deleteStateEnded($eventId, $id);
            }
        }
        $this->return = $this->Stages->get($saved->id);
    }

    private function _isUserAllowedInEvent(string $eventId): void
    {
        $userId = $this->getLocalOauth()->verifyAuthorizationAndGetToken()->getUserId();
        $this->Stages->Events->getEventFromUser($eventId, $userId);
    }

    private function _isUserAllowedInStage(string $eventId, string $stageId): void
    {
        $this->_isUserAllowedInEvent($eventId);
        $stage = $this->Stages->find()->where(['id' => $stageId, 'event_id' => $eventId])->first();
        if (!$stage) {
            throw new ForbiddenException('The stage is not from this event');
        }
    }
}
