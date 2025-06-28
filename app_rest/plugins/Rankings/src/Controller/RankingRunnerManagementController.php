<?php

declare(strict_types = 1);

namespace Rankings\Controller;

use App\Model\Table\UsersTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Rankings\Model\Table\RankingsTable;
use RestApi\Lib\Exception\DetailedException;
use Results\Controller\ApiController;
use Results\Lib\Consts\UploadTypes;
use Results\Model\Entity\Runner;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StageOrdersTable;

class RankingRunnerManagementController extends ApiController
{
    private RankingsTable $Rankings;
    private UsersTable $Users;
    private StageOrdersTable $StageOrders;

    public function initialize(): void
    {
        parent::initialize();
        $this->Rankings = RankingsTable::load();
        $this->Users = UsersTable::load();
        $this->StageOrders = StageOrdersTable::load();
    }

    protected function addNew($data)
    {
        $rankingId = $this->request->getParam('rankingID');
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');

        $this->Users->getManagerOrFail($this->OAuthServer->getUserID());

        if (
            !isset($data['runner_id'])
            || !isset($data['stage_order'])
            || !isset($data['upload_type'])
        ) {
            throw new BadRequestException('Mandatory payload runner_id stage_order upload_type');
        }
        $rk = $this->Rankings->getCached($rankingId);
        if ($rk->getEventId() !== $eventId) {
            throw new ForbiddenException('Invalid rankingID or eventID ' . $eventId);
        }
        if ($rk->getStageId() !== $stageId) {
            throw new ForbiddenException('Invalid rankingID or stageID ' . $stageId);
        }

        if ($data['upload_type'] === UploadTypes::COMPUTABLE_ORGANIZER) {
            $amount = $this->StageOrders->getAllInStage($stageId)->count();
            $stageOrder = (int)$data['stage_order'];
            if ($stageOrder < 1 || $stageOrder > $amount) {
                throw new DetailedException('stage_order must match existing stages ' . $amount);
            }
            /** @var Runner $runner */
            $runner = RunnersTable::load()->find()->where([
                RunnersTable::field('id') => $data['runner_id'],
                'event_id' => $eventId,
                'stage_id' => $stageId
            ])->first();
            if (!$runner) {
                throw new DetailedException('Invalid runner_id');
            }
            $res = $this->Rankings->saveAsOrganizer($rk, $data['runner_id'], $runner->class_id, $stageOrder);
        } else {
            throw new BadRequestException('Invalid upload_type value');
        }
        $this->return = $res;
    }
}
