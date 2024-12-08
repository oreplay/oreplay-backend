<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Consts\CacheGrp;
use App\Lib\Exception\InvalidPayloadException;
use Cake\Cache\Cache;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ClassEntity;
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

    private function _clearUploadCache()
    {
        Cache::clear(CacheGrp::UPLOAD);
        $this->runnersTable()->emptyStoredList();
    }

    private function _addNew(UploadHelper $helper): array
    {
        $this->_clearUploadCache();
        $metrics = new UploadMetrics();
        $metrics->startProcessing();
        //$this->log('Uploading data: ' . " \n\n" . json_encode($helper->getData()), \Psr\Log\LogLevel::DEBUG);
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($helper->getEventId(), $token);
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }

        $configChecker = $helper->validateConfigChecker();
        $stageId = $helper->getStageId();

        $helper->setExistingRunnerResults($this->runnersTable()->RunnerResults->getAllResults($helper));

        if ($configChecker->isStartLists()) {
            if ($helper->hasAlreadyFinishTimes()) {
                throw new InvalidPayloadException('Cannot add start times when there are already finish times');
            }
        }

        $classesToSave = [];
        foreach ($configChecker->getClasses() as $classObj) {
            $class = $this->Classes->createIfNotExists($helper->getEventId(), $stageId, $classObj);
            if (!$class->isSameUploadHash($classObj)) {
                // if no change is done in the whole class, we could totally skip processing it
                $course = $this->Classes->Courses->createIfNotExists($helper->getEventId(), $stageId, $classObj);
                $class->course = $course;
                $class = $this->_addAllRunnersInClass($classObj, $class, $helper);
                $metrics->addToRunnerCounter(count($class->runners));
                $classesToSave[] = $class;
            }
        }

        $metrics->addSplitsMetrics($this->runnersTable()->RunnerResults->Splits);
        $metrics->addRunnerMetrics($this->runnersTable());
        $this->_clearUploadCache();

        $metrics->saveManyOrFail($this->Classes, $classesToSave);

        $develop = $this->getRequest()->getQuery('develop');
        if (!$develop || $develop < 301) {
            return $metrics->toArrayLegacy($configChecker->preCheckType());
        }
        return $metrics->toArray($configChecker->preCheckType());
    }

    private function _addAllRunnersInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->runnersTable()->ifDifferentClassEmptyStoredList($class->id);
        $runners = [];
        foreach ($classArray['runners'] as $runnerData) {
            $runners[] = $this->runnersTable()->createRunnerWithResults($runnerData, $class, $helper);
        }
        $class->runners = $runners;
        return $class;
    }

    /**
     * @return RunnersTable
     */
    private function runnersTable()
    {
        return $this->Classes->Runners;
    }

    protected function addNew($data)
    {
        $this->Classes = ClassesTable::load();
        $this->flatResponse = true;
        try {
            $helper = new UploadHelper($data, $this->request->getParam('eventID'));
            $this->return = $this->_addNew($helper);
        } catch (\PDOException $e) {
            $this->log('Uploads PDOException: ' . $e->getMessage()
                . " \n\n" . json_encode($data)
                . " \n\n" . json_encode($this->return)
            );
            $this->return = $this->respondError($e->getMessage(), $e->getCode());
        } catch (DetailedException $e) {
            $this->log('Uploads DetailedException: ' . $e->getMessage() . " \n" . json_encode($data));
            $this->return = $this->respondError($e->getMessage(), $e->getCode());
        }
    }

    private function respondError(string $message, $code): array
    {
        $now = new FrozenTime();
        $this->response = $this->response->withStatus(202);
        return [
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

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }
}
