<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Exception\InvalidPayloadException;
use Cake\Http\Exception\ForbiddenException;
use Results\Lib\UploadConfigChecker;
use Results\Model\Entity\ClassEntity;
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
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = $token === EventsController::FAKE_TOKEN;
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }
        $this->Classes = ClassesTable::load();
        $checker = new UploadConfigChecker($data);
        $eventId = $this->request->getParam('eventID');
        list($data, $stageId) = $checker->validateStructure($eventId);
        if ($checker->isStartLists()) {
            if ($this->Classes->Runners->RunnerResults->hasFinishTimes($eventId, $stageId)) {
                throw new InvalidPayloadException('Cannot add start times when there are finish times');
            }
        }

        $runnerCount = 0;
        $classes = [];
        foreach ($data as $classObj) {
            /** @var ClassEntity $class */
            $class = $this->Classes->createIfNotExists($eventId, $stageId, $classObj);
            $course = $this->Classes->Courses->createIfNotExists($eventId, $stageId, $classObj);
            $class->course = $course;
            $runners = [];
            foreach ($classObj['runners'] as $runnerData) {
                $runner = $this->Classes->Runners->createRunnerIfNotExists(
                    $eventId, $stageId, $runnerData, $class);
                $results = $runnerData['runner_results'] ?? [];
                foreach ($results as $resultData) {
                    /** @var RunnerResult $result */
                    $result = $this->Classes->Runners->RunnerResults
                        ->patchFromNewWithUuid($resultData);
                    $result->event_id = $eventId;
                    $result->stage_id = $stageId;
                    $typeId = $resultData['result_type']['id'] ?? null;
                    if (!$typeId) {
                        throw new InvalidPayloadException('runner_results.result_type.id is mandatory');
                    }
                    $result->result_type = $this->Classes->Runners->RunnerResults->ResultTypes
                        ->getCached($typeId);
                    if (!isset($runner->runner_results)) {
                        $runner->runner_results = [];
                    }
                    $runner->runner_results[] = $result;
                }
                $runnerClub = $runnerData['club'] ?? null;
                if ($runnerClub) {
                    $runner->club = $this->Classes->Runners->Clubs
                        ->createIfNotExists($eventId, $stageId, $runnerClub);
                }
                $runners[] = $runner;
                $runnerCount++;
            }
            $class->runners = $runners;
            $classes[] = $class;
        }
        $this->Classes->saveManyOrFail($classes);
        $this->flatResponse = true;
        $classCount = count($classes);
        $this->return = [
            'data' => $classes,
            'meta' => [
                'updated' => [
                    'classes' => $classCount,
                    'runners' => $runnerCount,
                ],
                'human' => [
                    "Updated $classCount classes",
                    "Updated $runnerCount runners",
                ]
            ]
        ];
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }
}
