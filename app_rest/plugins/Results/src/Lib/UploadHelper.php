<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Http\Exception\InternalErrorException;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\Import\IntermediateStations;
use Results\Lib\Import\RowsToInsert;
use Results\Lib\Import\SplitsToReplace;
use Results\Model\Entity\Runner;
use Results\Model\Entity\RunnerResult;
use Results\Model\Entity\Team;
use Results\Model\Entity\TeamResult;
use Results\Model\Table\RawUploadsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\StagesTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;

class UploadHelper
{
    private array $_data;
    private string $_eventId;
    private string $_classId = '';
    private string $_courseId = '';
    private string $_stageTypeId = '';
    private UploadConfigChecker $_checker;
    private ExistingResultsIndex $_existingResults;
    private UploadMetrics $_metrics;
    private SplitsToReplace $_splitsToReplace;
    private RowsToInsert $_rowsToInsert;
    private IntermediateStations $_intermediateStations;
    private bool $_reprocessAll = false;

    public function __construct(array $data, string $eventID, UploadMetrics $metrics)
    {
        if (!$data) {
            throw new InternalErrorException('Payload $data is mandatory');
        }
        if (!$eventID) {
            throw new InternalErrorException('$eventID is mandatory');
        }
        $this->_data = $data;
        $this->_eventId = $eventID;
        $uploadLogId = '';
        if ($uploadLogId) {
            $this->_data = $this->_loadFromRawUploads($uploadLogId);
        }
        $this->_metrics = $metrics;
        $this->_existingResults = new ExistingResultsIndex();
        $this->_splitsToReplace = new SplitsToReplace();
        $this->_rowsToInsert = new RowsToInsert();
        $this->_intermediateStations = new IntermediateStations();
        $this->_forgetParticipantsFromPreviousUploads();
    }

    private function _forgetParticipantsFromPreviousUploads(): void
    {
        RunnersTable::load()->emptyStoredList();
        TeamsTable::load()->emptyStoredList();
    }

    private function _loadFromRawUploads(string $uploadLogId): array
    {
        if (!$uploadLogId) {
            return $this->_data;
        }
        $oldChecker = new UploadConfigChecker($this->_data);
        $newStageId = $oldChecker->validateStructure($this->_eventId)->getStageId();

        $raw = RawUploadsTable::load()->getByUploadLogId($uploadLogId);
        $this->_data = json_decode($raw->file_data, true);
        $this->_data['oreplay_data_transfer']['event']['id'] = $this->_eventId;
        $this->_data['oreplay_data_transfer']['event']['stages'][0]['id'] = $newStageId;
        return $this->_data;
    }

    public function isArrayWithoutValues(array $data): bool
    {
        $values = implode('', $data);
        return $values === '';
    }

    public function getMetrics(): UploadMetrics
    {
        return $this->_metrics;
    }

    public function getExistingResults(): ExistingResultsIndex
    {
        return $this->_existingResults;
    }

    public function getSplitsToReplace(): SplitsToReplace
    {
        return $this->_splitsToReplace;
    }

    public function getRowsToInsert(): RowsToInsert
    {
        return $this->_rowsToInsert;
    }

    public function getIntermediateStations(): IntermediateStations
    {
        return $this->_intermediateStations;
    }

    public function getContext(): UploadContext
    {
        return new UploadContext($this->_eventId, $this->getStageId(), $this->_classId, $this->_courseId);
    }

    public function reprocessAll(): void
    {
        $this->_reprocessAll = true;
    }

    public function isReprocessingAll(): bool
    {
        return $this->_reprocessAll;
    }

    public function inClass(string $classId): self
    {
        $inClass = clone $this;
        $inClass->_classId = $classId;
        return $inClass;
    }

    public function runningCourse(string $courseId): self
    {
        $inCourse = clone $this;
        $inCourse->_courseId = $courseId;
        return $inCourse;
    }

    public function getData(): array
    {
        return $this->_data;
    }

    public function getEventId(): string
    {
        return $this->_eventId;
    }

    public function getStageId(): string
    {
        return $this->_checker->getStageId();
    }

    /**
     * The same data reaching us from different sources has to produce the same hash. Key order,
     * numbers written as strings, and empty-versus-absent all differ between the JSON client and IOF
     * XML for an identical event, and this hash is the resume mechanism rather than a cache: when it
     * mismatches, every class looks changed, so two feeds pointed at one stage keep invalidating each
     * other's work and a large event may never converge.
     */
    public static function md5Encode(array $array): string
    {
        return md5(json_encode(self::_canonical($array))); // NOSONAR
    }

    private static function _canonical(array $array): array
    {
        $isList = array_is_list($array);
        $canonical = [];
        foreach ($array as $key => $value) {
            $value = is_array($value) ? self::_canonical($value) : self::_comparableScalar($value);
            if ($value === null || $value === []) {
                continue;
            }
            $canonical[$key] = $value;
        }
        if ($isList) {
            return array_values($canonical);
        }
        ksort($canonical);
        return $canonical;
    }

    private static function _comparableScalar(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        return $value === true ? '1' : (string)$value;
    }

    public function setConfigChecker(UploadConfigChecker $checker): void
    {
        $this->_checker = $checker;
    }

    public function validateConfigChecker(): UploadConfigChecker
    {
        $this->setConfigChecker(new UploadConfigChecker($this->_data));
        if ($this->_checker->isTotals()) {
            $Stages = StagesTable::load();
            if (!$this->_checker->isStageTotals($Stages)) {
                $stage = $Stages->getOrCreateTotalsInEvent($this->getEventId());
                $this->_checker->overwriteStageId($stage->id);
            }
        }

        $checker = $this->_checker->validateStructure($this->getEventId());

        $this->_validateStageInEvent($this->getEventId(), $this->getStageId());

        return $checker;
    }

    private function _validateStageInEvent($eventId, string $stageId): void
    {
        $stage = StagesTable::load()->findByEvent($stageId, $eventId)->first();
        if (!$stage) {
            throw new DetailedException("The stage $stageId is not from the event $eventId");
        }
    }

    public function loadExistingResults(RunnerResultsTable $RunnerResults, TeamResultsTable $TeamResults): void
    {
        $context = $this->getContext();
        $this->_existingResults->indexRunnerResults($RunnerResults->getAllResults($context));
        $this->_existingResults->indexTeamResults($TeamResults->getAllResults($context));
        $this->_existingResults->indexControls($RunnerResults->Splits->Controls->getAllControls($context));
    }

    public function processRunnerResults(RunnerResult|TeamResult $resultToSave, Runner|Team $participant): Runner|Team
    {
        $existingResults = $this->_existingResults->getExistingDbResults($participant, $resultToSave);
        $existingResultAmount = count($existingResults);
        if ($existingResultAmount) {
            if ($existingResultAmount === 1) {
                // if there is only one existing result, we reuse the ID to replace the db row
                $resultToSave->setIDsToUpdate($existingResults[0]);
            } else {
                // if there is more than one result, we remove them all to avoid duplicates
                $participant = $participant->removeAllExistingResults($existingResults);
            }
            $isRadiosAfterDownload = $resultToSave->isIntermediates() && $existingResults[0]->isSplits();
            if ($isRadiosAfterDownload) {
                $resultToSave->setUploadTypeSplits();
            }
        }
        return $participant;
    }

    public function getStageTypeId(): string
    {
        if (!$this->_stageTypeId) {
            $this->_stageTypeId = StagesTable::load()->getStageTypeId($this->getStageId());
        }
        return $this->_stageTypeId;
    }

    public function getChecker(): UploadConfigChecker
    {
        return $this->_checker;
    }

    public function hasAlreadyFinishTimes()
    {
        return RunnerResultsTable::load()
            ->hasFinishTimes($this->getEventId(), $this->getStageId());
    }
}
