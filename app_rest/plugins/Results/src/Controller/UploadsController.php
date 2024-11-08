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
    }

    private function _addNew(UploadHelper $helper): array
    {
        $startsTime = microtime(true);
        //$this->log('Uploading data: ' . " \n\n" . json_encode($helper->getData()), \Psr\Log\LogLevel::DEBUG);
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($helper->getEventId(), $token);
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }
        $this->Classes = ClassesTable::load();

        $configChecker = $helper->validateConfigChecker();
        $stageId = $helper->getStageId();

        $helper->setExistingRunnerResults($this->runnersTable()->RunnerResults->getAllResults($helper));

        if ($configChecker->isStartLists()) {
            if ($helper->hasAlreadyFinishTimes()) {
                throw new InvalidPayloadException('Cannot add start times when there are already finish times');
            }
        }

        $runnerCount = 0;
        $classesToSave = [];
        foreach ($configChecker->getClasses() as $classObj) {
            $class = $this->Classes->createIfNotExists($helper->getEventId(), $stageId, $classObj);
            $course = $this->Classes->Courses->createIfNotExists($helper->getEventId(), $stageId, $classObj);
            $class->course = $course;
            $class = $this->_addAllRunnersInClass($classObj, $class, $helper);
            $runnerCount += count($class->runners);
            $classesToSave[] = $class;
        }

        $classCount = count($classesToSave);
        $type = $configChecker->preCheckType();
        $now = new FrozenTime();
        $duration = round(microtime(true) - $startsTime, 2);
        return [
            'data' => $classesToSave,
            'meta' => [
                'updated' => [
                    'classes' => $classCount,
                    'runners' => $runnerCount,
                ],
                'human' => [
                    "Updated $runnerCount runners, $classCount classes ($now - $type) in $duration secs.",
                ]
            ]
        ];
    }

    private function _addAllRunnersInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
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
        $this->flatResponse = true;
        try {
            $this->_clearUploadCache();
            $helper = new UploadHelper($data, $this->request->getParam('eventID'));
            $this->return = $this->_addNew($helper);
            $this->_clearUploadCache();
            $this->Classes->saveManyOrFail($this->return['data']);
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
