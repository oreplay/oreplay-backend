<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Exception\InvalidPayloadException;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\UploadConfigChecker;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\RunnerResult;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TokensTable;

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

    private function _addNew($data): array
    {
        $eventId = $this->request->getParam('eventID');
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($eventId, $token);
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }
        $this->Classes = ClassesTable::load();
        $checker = new UploadConfigChecker($data);
        list($data, $stageId) = $checker->validateStructure($eventId);
        if ($checker->isStartLists()) {
            if ($this->Classes->Runners->RunnerResults->hasFinishTimes($eventId, $stageId)) {
                throw new InvalidPayloadException('Cannot add start times when there are already finish times');
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
        $classCount = count($classes);
        return [
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

    protected function addNew($data)
    {
        $this->flatResponse = true;
        try {
            $this->return = $this->_addNew($data);
        } catch (DetailedException $e) {
            $now = new FrozenTime();
            $message = $e->getMessage();
            $code = $e->getCode();
            $this->response = $this->response->withStatus(200); // TODO change to 202 after desktop-client 0.2.5
            $this->return = [
                'data' => null,
                'meta' => [
                    'updated' => [
                        'classes' => 0,
                        'runners' => 0,
                    ],
                    'human' => [
                        "[Error - $code] ($now) $message",
                    ]
                ]
            ];
        }
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
