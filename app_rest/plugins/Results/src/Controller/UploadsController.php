<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Exception\InvalidPayloadException;
use Cake\Http\Exception\ForbiddenException;
use Results\Model\Entity\ClassEntity;
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
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = $token === EventsController::FAKE_TOKEN;
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }
        $this->Classes = ClassesTable::load();
        $eventId = $this->request->getParam('eventID');
        if (!isset($data['oreplay_data_transfer'])) {
            throw new InvalidPayloadException('Invalid payload structure oreplay_data_transfer must be root element');
        }
        $data = $data['oreplay_data_transfer'];
        if (!isset($data['event']['id'])) {
            throw new InvalidPayloadException('Invalid payload structure event.id');
        }
        if ($data['event']['id'] !== $eventId) {
            throw new InvalidPayloadException('Event id must match');
        }

        $firstStage = $data['event']['stages'][0] ?? null;
        if ($firstStage) {
            $data = $firstStage;
        } else {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0');
        }
        $stageId = $firstStage['id'] ?? null;
        if (!$stageId) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.id');
        }
        $data = $data['classes'] ?? null;
        if (!is_array($data)) {
            throw new InvalidPayloadException('Invalid payload structure event.stages.0.classes');
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
                $runner = $this->Classes->Runners->createRunnerIfNotExists($eventId, $stageId, $runnerData);
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
