<?php

declare(strict_types = 1);

namespace RadioRelay\Lib\Cpi;

use Cake\Log\LogTrait;
use Psr\Log\LogLevel;
use Results\Lib\ExistingResultsIndex;
use Results\Lib\UploadContext;
use Results\Model\Entity\Runner;
use Results\Model\Entity\Split;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;

class ProcessPunches
{
    use LogTrait;

    private PayloadParser $data;
    private ExistingResultsIndex $existingResults;
    private RunnersTable $Runners;
    private SplitsTable $Splits;

    public function __construct(PayloadParser $data)
    {
        $this->data = $data;
        $this->existingResults = new ExistingResultsIndex();
    }

    public function setRunnersTable(RunnersTable $table)
    {
        $this->Runners = $table;
        /** @var SplitsTable $splitsTable */
        $splitsTable = $table->RunnerResults->Splits->getTarget();
        $this->Splits = $splitsTable;
        return $this;
    }

    public function process(): array
    {
        $this->_indexStoredControls();
        $punchAmount = 0;
        $stations = [];
        foreach ($this->data->getPunches() as $punch) {
            $this->_processPunch($punch);
            if ($punch['station'] ?? null) {
                $stations[] = (string)$punch['station'];
            }
            $punchAmount++;
        }
        $this->Splits->Controls->markIntermediateStations($this->data->getStageId(), array_unique($stations));
        $lastId = '1'; // must be numeric
        return ['OK', '' . $punchAmount, $lastId];
    }

    // without this the index starts empty on every request, so a station already stored by an
    // upload or by an earlier punch is not recognised and a second control row is created for it
    private function _indexStoredControls(): void
    {
        $context = new UploadContext($this->data->getEventId(), $this->data->getStageId());
        $this->existingResults->indexControls($this->Splits->Controls->getAllControls($context));
    }

    private function _processPunch(array $punch): ?Split
    {
        $eventId = $this->data->getEventId();
        $stageId = $this->data->getStageId();
        $timezone = $this->data->getTimezone();
        $siCard = $punch['sicard'] ?? null;
        /** @var Runner $runner */
        $runner = $this->Runners->findByCard($siCard, $eventId, $stageId)->first();
        if (!$runner) {
            $this->log(
                'CpiServerController: [32] no_runner in event ' . $eventId . ' ' . $stageId
                . ' discarded punch sicard ' . $siCard . ' station ' . ($punch['station'] ?? '')
                . ' reading ' . ($punch['reading'] ?? ''),
                LogLevel::WARNING
            );
            return null;
        }
        $split = [
            'is_intermediate' => true,
            'station' => $punch['station'] ?? null,
            'reading_time' => PayloadParser::getReadingTime($punch, $timezone),
            'battery_perc' => $punch['battery'] ?? null,
            'battery_time' => $punch['reading'] ?? null,
            'raw_value' => $punch['raw'] ?? null,
        ];
        $splitToSave = $this->Splits->fillNewWithStage($split, $eventId, $stageId);
        $splitToSave->class_id = $runner->class_id;
        $splitToSave->runner_id = $runner->id;
        $splitToSave->runner_result_id = $runner->_getStage()->id;
        $splitToSave->battery_perc = $punch['battery'] ?? null;
        $splitToSave->battery_time = $punch['reading'] ?? null;
        $context = new UploadContext($eventId, $stageId);
        $control = $this->Splits->Controls->createControlIfNotExists($context, $this->existingResults, $split);
        if ($this->existingResults->takeControlToWrite($control)) {
            $splitToSave->addControl($control);
        }
        $splitToSave->control_id = $control->id;
        /** @var Split $ret */
        $ret = $this->Splits->save($splitToSave);
        return $ret;
    }
}
