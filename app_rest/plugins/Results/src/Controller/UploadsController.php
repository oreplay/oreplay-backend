<?php

declare(strict_types = 1);

namespace Results\Controller;

use App\Lib\Consts\CacheGrp;
use App\Lib\Exception\InvalidPayloadException;
use App\Lib\Exception\InvalidTokenException;
use App\Lib\FullBaseUrl;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\Import\CourseImporter;
use Results\Lib\Import\RunnerImporter;
use Results\Lib\Import\TeamImporter;
use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Team;
use Results\Model\Table\ClassesTable;
use Results\Model\Table\ControlsTable;
use Results\Model\Table\CourseControlsTable;
use Results\Model\Table\CoursesTable;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;
use Results\Model\Table\TokensTable;
use Results\Model\Table\UploadLogsTable;

class UploadsController extends ApiController
{
    public const NEW_VERSION = 402;

    private UploadMetrics $_metrics;
    private ClassesTable $Classes;

    public function isPublicController(): bool
    {
        return true;
    }

    private function _clearUploadCache()
    {
        Cache::clearGroup(CacheGrp::UPLOAD_ENTITIES_GROUP, CacheGrp::UPLOAD);
    }

    private function _getHost()
    {
        $host = FullBaseUrl::host();
        if (str_contains($host, 'http://')) {
            return 'http://www.example.com';
        }
        if (str_contains($host, '127.0.0.1')) {
            return 'http://localhost';
        }
        return $host;
    }

    private function _addNew(UploadHelper $helper): array
    {
        $this->_clearUploadCache();
        $metrics = $helper->getMetrics();
        if (Configure::read('debug')) {
            $this->_writeLastUploadJson($helper->getData(), TMP . 'lastUpload.json');
        }
        //$this->log('Uploading: ' . " \n\n" . json_encode($helper->getData()), \Psr\Log\LogLevel::DEBUG); // NOSONAR
        $token = $this->_getBearer();
        $isDesktopClientAuthenticated = TokensTable::load()->isValidEventToken($helper->getEventId(), $token);
        if (!$isDesktopClientAuthenticated) {
            throw new InvalidTokenException(
                'There is a problem with the token, create a new one and set it in the client');
        }

        $configChecker = $helper->validateConfigChecker();
        $stageId = $helper->getStageId();

        //$rawUrl = $this->_getHost() . '/api/v1/events/' . $helper->getEventId() . '/rawUploads';
        //FireAndForget::postJson($rawUrl, $helper->getData(), ['Authorization' => 'Bearer ' . $token]);

        $helper->loadExistingResults($this->runnerResultsTable(), $this->teamResultsTable());

        if ($configChecker->isStartLists() && $helper->hasAlreadyFinishTimes()) {
            throw new InvalidPayloadException('Cannot add start times when there are already finish times');
        }

        $counter = 0;
        foreach ($configChecker->getClasses() as $classObj) {
            $class = $this->Classes->createIfNotExists($helper->getEventId(), $stageId, $classObj);
            $isTakingTooLong = $this->_setIsTakingTooLongWarning($metrics, $counter);
            if ($this->_needsProcessing($class, $classObj, $helper) && !$isTakingTooLong) {
                $class->setHash($classObj);
                // if no change is done in the whole class, we could totally skip processing it
                $class = $this->_addCourseToClass($classObj, $class, $helper);
                $classHelper = $helper->inClass($class->id)->runningCourse($class->course->id ?? '');
                $class = $this->_addAllRunnersInClass($classObj, $class, $classHelper);
                $class = $this->_addAllTeamsInClass($classObj, $class, $classHelper);
                $metrics->saveManyOrFail(
                    $this->Classes,
                    $class,
                    $helper->getSplitsToReplace(),
                    $helper->getRowsToInsert()
                );
                $this->_storeCourseOf($class, $helper);
                $counter++;
            }
        }

        $this->_markIntermediateStations($helper);

        $log = UploadLogsTable::load()->saveUploadLog($helper);
        RawUploadsTable::load()->saveFile($log, $helper);

        $metrics->endTotalTimer();

        $queryParam = $this->getRequest()->getQuery('version');
        if (!$queryParam || $queryParam < UploadsController::NEW_VERSION) {
            return $metrics->toArrayLegacy($configChecker->preCheckType());
        }
        return $metrics->toArray($configChecker->preCheckType());
    }

    private function _addCourseToClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $courseArray = $classArray['course'] ?? [];
        if (!$courseArray) {
            return $class;
        }
        $metrics = $helper->getMetrics();
        $course = $metrics->measure(
            UploadMetrics::COURSES,
            fn() => $this->Classes->Courses->createIfNotExists(
                $helper->getEventId(),
                $helper->getStageId(),
                $courseArray
            )
        );
        $class->course = $course;
        $metrics->addCourse($course);
        return $class;
    }

    private function _storeCourseOf(ClassEntity $class, UploadHelper $helper): void
    {
        $importer = new CourseImporter(CourseControlsTable::load(), CoursesTable::load(), $helper);
        $importer->importInto($class);
    }

    private function _markIntermediateStations(UploadHelper $helper): void
    {
        ControlsTable::load()->markIntermediateStations(
            $helper->getStageId(),
            $helper->getIntermediateStations()->toList()
        );
    }

    private function _addAllRunnersInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->runnersTable()->ifDifferentClassEmptyStoredList($class->id);
        $importer = new RunnerImporter($this->runnersTable(), $helper);
        $runnerArray = $classArray['runners'] ?? [];
        $runners = $helper->getMetrics()->measure(
            UploadMetrics::PARTICIPANTS_LOOP,
            fn() => $this->_importEachRunner($runnerArray, $class, $importer, $helper)
        );
        $class->addRunners($runners);
        return $class;
    }

    /**
     * @return Runner[]
     */
    private function _importEachRunner(
        array $runnerArray,
        ClassEntity $class,
        RunnerImporter $importer,
        UploadHelper $helper
    ): array {
        $metrics = $helper->getMetrics();
        $runners = [];
        $existingRunnerIDs = [];
        foreach ($runnerArray as $runnerData) {
            $runner = $metrics->measure(
                UploadMetrics::PARTICIPANTS_IN_LOOP,
                fn() => $importer->import($runnerData, $class)
            );
            if (in_array($runner->id, $existingRunnerIDs)) {
                // warning: two payload entries can match one runner (same bib, or same name in the class,
                // see Runner::getMatchedRunner) so two real people silently become one row and one set of
                // results. Dropping the duplicate here only avoids saving it twice; the merge still happens.
                $metrics->setWarning('Duplicated runner ' . $runner->_getFullName() . ' ' . $runner->bib_number);
                continue;
            }
            $existingRunnerIDs[] = $runner->id;
            $runners[] = $runner;
        }
        return $runners;
    }

    private function _addAllTeamsInClass(array $classArray, ClassEntity $class, UploadHelper $helper): ClassEntity
    {
        $this->teamsTable()->ifDifferentClassEmptyStoredList($class->id);
        $importer = new TeamImporter($this->teamsTable(), $helper);
        $teamArray = $classArray['teams'] ?? [];
        $class->teams = $helper->getMetrics()->measure(
            UploadMetrics::PARTICIPANTS_LOOP,
            fn() => $this->_importEachTeam($teamArray, $class, $importer, $helper)
        );
        return $class;
    }

    /**
     * @return Team[]
     */
    private function _importEachTeam(
        array $teamArray,
        ClassEntity $class,
        TeamImporter $importer,
        UploadHelper $helper
    ): array {
        $metrics = $helper->getMetrics();
        $teams = [];
        foreach ($teamArray as $teamData) {
            // warning: unlike _importEachRunner() this has no duplicate detection, so two teams
            // sharing a bib (see Team::getMatchedRunner) are matched as one team and appended twice.
            // No upload example carries duplicated team bibs, so nothing exercises it.
            $teams[] = $metrics->measure(
                UploadMetrics::PARTICIPANTS_IN_LOOP,
                fn() => $importer->import($teamData, $class)
            );
        }
        return $teams;
    }

    private function teamsTable(): TeamsTable
    {
        return $this->Classes->Teams->getTarget();
    }

    private function runnersTable(): RunnersTable
    {
        return $this->Classes->Runners->getTarget();
    }

    private function runnerResultsTable(): RunnerResultsTable
    {
        return $this->runnersTable()->RunnerResults->getTarget();
    }

    private function teamResultsTable(): TeamResultsTable
    {
        return $this->teamsTable()->TeamResults->getTarget();
    }

    protected function addNew($data)
    {
        $this->Classes = ClassesTable::load();
        $this->flatResponse = true;
        $this->_metrics = new UploadMetrics();
        try {
            $reUploadedData = RawUploadsTable::load()->getReUploadedData($data, $this->request->getParam('eventID'));
            if ($reUploadedData) {
                $data = $reUploadedData;
            }
            $helper = new UploadHelper($data, $this->request->getParam('eventID'), $this->_metrics);
            if ($this->_isReprocessAllRequested()) {
                $helper->reprocessAll();
            }
            $this->return = $this->_addNew($helper);
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
        } catch (\Throwable $e) {
            $this->log('Uploads GeneralException: ' . $e->getMessage() . " \n" . json_encode($data)
                . " \n" . $e->getTraceAsString());
            $exploded = explode('\\', get_class($e));
            $exceptionName = array_pop($exploded);
            if (!$exceptionName) {
                $exceptionName = array_pop($exploded);
            }
            $this->return = $this->respondError($exceptionName, $e->getCode());
        } finally {
            $this->_clearUploadCache();
        }
    }

    private function respondError(string $message, $code): array
    {
        $now = new FrozenTime();
        $this->response = $this->response->withStatus(202);
        return $this->_metrics->toArrayError(["\n    [ERROR - $code] ($now) $message \n"]);
    }

    private function _getBearer(): ?string
    {
        $auth = $this->getRequest()->getHeader('Authorization')[0] ?? null;
        if (!$auth) {
            return null;
        }
        return substr($auth, strlen('Bearer '));
    }

    private function _needsProcessing(ClassEntity $class, array $classObj, UploadHelper $helper): bool
    {
        return $helper->isReprocessingAll() || !$class->isSameUploadHash($classObj);
    }

    private function _isReprocessAllRequested(): bool
    {
        return filter_var($this->getRequest()->getQuery('reprocess_all'), FILTER_VALIDATE_BOOLEAN);
    }

    private function _setIsTakingTooLongWarning(UploadMetrics $metrics, int $counter): bool
    {
        $isTakingTooLong = $metrics->isTakingTooLong();
        if ($isTakingTooLong) {
            $msg1 = 'It is taking too long, ';
            $msg2 = $counter ? 'some data was already processed, but ' : '';
            $msg3 = 'you need to upload again to finish processing';
            $metrics->setWarning($msg1 . $msg2 . $msg3);
        }
        return $isTakingTooLong;
    }

    private function _writeLastUploadJson(array $content, string $path)
    {
        $file = new \SplFileObject($path, 'w+');
        $file->fwrite(json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
