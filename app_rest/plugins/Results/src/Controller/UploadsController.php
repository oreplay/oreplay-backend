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
use Results\Model\Table\TeamsTable;
use Results\Model\Table\TokensTable;

/**
 * @property RunnersTable $Runners
 * @property ClassesTable $Classes
 */
class UploadsController extends ApiController
{
    private UploadHelper $_helper;

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
        $metrics = $helper->getMetrics();
        //$this->_writeLastUploadJson($helper->getData(), TMP . 'lastUpload.json');
        //$this->log('Uploading: ' . " \n\n" . json_encode($helper->getData()), \Psr\Log\LogLevel::DEBUG); // NOSONAR
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($helper->getEventId(), $token);
        if (!$isDesktopClientAuthenticated) {
            throw new ForbiddenException('Invalid Bearer token');
        }

        $configChecker = $helper->validateConfigChecker();
        $stageId = $helper->getStageId();

        $helper->setExistingData($this->runnersTable()->RunnerResults, $this->teamsTable()->TeamResults);

        if ($configChecker->isStartLists() && $helper->hasAlreadyFinishTimes()) {
            throw new InvalidPayloadException('Cannot add start times when there are already finish times');
        }

        foreach ($configChecker->getClasses() as $classObj) {
            $class = $this->Classes->createIfNotExists($helper->getEventId(), $stageId, $classObj);
            if (!$class->isSameUploadHash($classObj)) {
                $class->setHash($classObj);
                $this->_helper->setCurrentClassId($class->id);
                $helper->getMetrics()->startCoursesTime();
                // if no change is done in the whole class, we could totally skip processing it
                $course = $this->Classes->Courses->createIfNotExists($helper->getEventId(), $stageId, $classObj);
                $class->course = $course;
                $helper->getMetrics()->endCoursesTime();
                $class = $this->_addAllRunnersInClass($classObj, $class, $helper);
                $class = $this->_addAllTeamsInClass($classObj, $class, $helper);
                $metrics->saveManyOrFail($this->Classes, $class);
            }
        }

        $this->_clearUploadCache();

        $metrics->endTotalTimer();

        $queryParam = $this->getRequest()->getQuery('version');
        if (!$queryParam || $queryParam < 402) {
            return $metrics->toArrayLegacy($configChecker->preCheckType());
        }
        return $metrics->toArray($configChecker->preCheckType());
    }

    private function _addAllRunnersInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->runnersTable()->ifDifferentClassEmptyStoredList($class->id);
        $runners = [];
        $runnerArray = $classArray['runners'] ?? [];
        $runnerCount = count($runnerArray);
        $helper->getMetrics()->startRunnersOutLoopTime();
        for ($i = 0; $i < $runnerCount; $i++) {
            $helper->getMetrics()->startRunnersInLoopTime();
            $runnerData = $runnerArray[$i];
            $runners[] = $this->runnersTable()->createRunnerWithResults($runnerData, $class, $helper);
            $helper->getMetrics()->endRunnersInLoopTime();
        }
        $helper->getMetrics()->endRunnersOutLoopTime();
        $class->runners = $runners;
        return $class;
    }

    private function _addAllTeamsInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->teamsTable()->ifDifferentClassEmptyStoredList($class->id);
        $teams = [];
        $runnerArray = $classArray['teams'] ?? [];
        $runnerCount = count($runnerArray);
        $helper->getMetrics()->startRunnersOutLoopTime();
        for ($i = 0; $i < $runnerCount; $i++) {
            $helper->getMetrics()->startRunnersInLoopTime();
            $runnerData = $runnerArray[$i];
            $teams[] = $this->teamsTable()->createTeamWithResults($runnerData, $class, $helper);
            $helper->getMetrics()->endRunnersInLoopTime();
        }
        $helper->getMetrics()->endRunnersOutLoopTime();
        $class->teams = $teams;
        return $class;
    }

    private function teamsTable(): TeamsTable
    {
        return $this->Classes->Teams->getTarget();
    }

    private function runnersTable(): RunnersTable
    {
        return $this->Classes->Runners->getTarget();
    }

    protected function addNew($data)
    {
        $this->Classes = ClassesTable::load();
        $this->flatResponse = true;
        try {
            $this->_helper = new UploadHelper($data, $this->request->getParam('eventID'));
            $this->return = $this->_addNew($this->_helper);
        } catch (\PDOException $e) {
            $this->log('Uploads PDOException: ' . $e->getMessage()
                . " \n\n" . json_encode($data)
                . " \n\n" . json_encode($this->return)
            );
            $this->return = $this->respondError($e->getMessage(), $e->getCode());
        } catch (DetailedException $e) {
            $this->log('Uploads DetailedException: ' . $e->getMessage() . " \n" . json_encode($data)
                . " \n" . $e->getTraceAsString());
            $this->return = $this->respondError($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->log('Uploads GeneralException: ' . $e->getMessage() . " \n" . json_encode($data)
                . " \n" . $e->getTraceAsString());
            $exploded = explode('\\', get_class($e));
            $exceptionName = array_pop($exploded);
            if (!$exceptionName) {
                $exceptionName = array_pop($exploded);
            }
            $this->return = $this->respondError($exceptionName, $e->getCode());
        }
    }

    private function respondError(string $message, $code): array
    {
        $now = new FrozenTime();
        $this->response = $this->response->withStatus(202);
        return $this->_helper->getMetrics()
            ->toArrayError(["\n    [ERROR - $code] ($now) $message \n"]);
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }

    private function _writeLastUploadJson(array $content, string $path)
    {
        $file = new \SplFileObject($path, 'w+');
        $file->fwrite(json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
