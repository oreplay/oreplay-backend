<?php

declare(strict_types = 1);

namespace Results\Controller;

use RestApi\Lib\Exception\DetailedException;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\ResultType;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnersTable;

/**
 * @property RunnersTable $Runners
 * @property ClassesTable $Classes
 */
class UploadsController extends ApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function addNew($data)
    {
        $this->Classes = ClassesTable::load();
        $eventId = $this->request->getParam('eventID');
        $stageId = $this->request->getParam('stageID');

        $firstStage = $data['events']['stages'][0] ?? null;
        if ($firstStage) {
            $data = $firstStage;
        }
        $data = $data['classes'] ?? null;
        if (!is_array($data)) {
            throw new DetailedException('Invalid payload structure events.stages.0.classes');
        }

        $classes = [];
        foreach ($data as $classObj) {
            /** @var ClassEntity $class */
            $class = $this->Classes->createIfNotExists($eventId, $stageId, $classObj);
            $course = $this->Classes->Courses->createIfNotExists($eventId, $stageId, $classObj);
            $class->course = $course;
            $runners = [];
            foreach ($classObj['runners'] as $runnerData) {
                /** @var Runner $runner */
                $runner = $this->Classes->Runners->patchFromNewWithUuid($runnerData);
                $runner->event_id = $eventId;
                $runner->stage_id = $stageId;
                $resultData = $runnerData['runner_results'] ?? null;
                if ($resultData) {
                    /** @var RunnerResult $result */
                    $result = $this->Classes->Runners->RunnerResults
                        ->patchFromNewWithUuid($resultData);
                    $result->event_id = $eventId;
                    $result->stage_id = $stageId;
                    $result->result_type_id = ResultType::STAGE;
                    $runner->runner_results = [$result];
                }
                $runnerClub = $runnerData['club'] ?? null;
                if ($runnerClub) {
                    $runner->club = $this->Classes->Runners->Clubs
                        ->createIfNotExists($eventId, $stageId, $runnerClub);
                }
                $runners[] = $runner;
            }
            $class->runners = $runners;
            $classes[] = $class;
        }
        $this->Classes->saveManyOrFail($classes);
        $this->return = $classes;
    }
}
