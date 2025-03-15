<?php

declare(strict_types = 1);

namespace RadioRelay\Lib\Cpi;

use Results\Model\Entity\Runner;
use Results\Model\Entity\Split;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\SplitsTable;

class ProcessPunches
{
    private PayloadParser $data;
    private RunnersTable $Runners;
    private SplitsTable $Splits;

    public function __construct(PayloadParser $data)
    {
        $this->data = $data;
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
        $punchAmount = 0;
        foreach ($this->data->getPunches() as $punch) {
            $this->_processPunch($punch);
            $punchAmount++;
        }
        $lastId = '1'; // must be numeric
        return ['OK', '' . $punchAmount, $lastId];
    }

    private function _processPunch(array $punch): ?Split
    {
        $eventId = $this->data->getEventId();
        $stageId = $this->data->getStageId();
        $timezone = $this->data->getTimezone();
        $siCard = $punch['sicard'] ?? null;
        /** @var Runner $runner */
        $runner = $this->Runners->findByCard($siCard, $eventId, $stageId)->first();
        $split = [
            'sicard' => $siCard,
            'is_intermediate' => true,
            'station' => $punch['station'] ?? null,
            'reading_time' => PayloadParser::getReadingTime($punch, $timezone),
            'battery_perc' => $punch['battery'] ?? null,
            'battery_time' => $punch['reading'] ?? null,
            'raw_value' => $punch['raw'] ?? null,
        ];
        $splitToSave = $this->Splits->fillNewWithStage($split, $eventId, $stageId);
        if ($runner) {
            $splitToSave->class_id = $runner->class_id;
            $splitToSave->runner_id = $runner->id;
        } else {
            $splitToSave->class_id = null;
            $splitToSave->runner_id = null;
        }
        // maybe add $splitToSave->bib_runner = $punch['bib_runner'] ?? null;
        // maybe add $splitToSave->runner_result_id = $runner->_getOverall()->id;
        $splitToSave->battery_perc = $punch['battery'] ?? null;
        $splitToSave->battery_time = $punch['reading'] ?? null;
        $control = $this->Splits->Controls->createControlIfNotExists($this->data, $split);
        $splitToSave->addControl($control);
        /** @var Split $ret */
        $ret = $this->Splits->save($splitToSave);
        return $ret;
    }
}
