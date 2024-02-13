<?php
declare(strict_types=1);

namespace Results\Controller;

use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\RunnersTable;

/**
 * @property RunnersTable $Runners
 */
class UploadsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        $this->Runners = RunnersTable::load();
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');

        $runners = [];
        foreach ($data as $runnerData) {
            /** @var Runner $runner */
            $runner = $this->Runners->patchFromNewWithUuid($runnerData);
            $runner->event_id = $eventId;
            $runner->stage_id = $stageId;
            $resultData = $runnerData['runner_results'] ?? null;
            if ($resultData) {
                /** @var RunnerResult $result */
                $result = $this->Runners->RunnerResults->patchFromNewWithUuid($resultData);
                $result->event_id = $eventId;
                $result->stage_id = $stageId;
                $result->result_type_id = ResultType::STAGE;
                $runner->runner_results = [$result];
            }
            $runners[] = $runner;
        }
        $this->Runners->saveManyOrFail($runners);
        $this->return = $runners;
    }
}
