<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Runner;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\RunnerResultsTable;
use Results\Model\Table\RunnersTable;

class RunnerImporter
{
    private RunnersTable $_runners;
    private ClubsTable $_clubs;
    private RunnerResultImporter $_results;
    private UploadHelper $_helper;

    public function __construct(RunnersTable $runners, UploadHelper $helper)
    {
        $this->_runners = $runners;
        /** @var ClubsTable $clubs */
        $clubs = $runners->Clubs->getTarget();
        $this->_clubs = $clubs;
        /** @var RunnerResultsTable $runnerResults */
        $runnerResults = $runners->RunnerResults->getTarget();
        $this->_results = new RunnerResultImporter($runnerResults, $helper);
        $this->_helper = $helper;
    }

    public function import(array $runnerData, ClassEntity $class): Runner
    {
        $runnerData = $this->_withLegNumberFromFirstResult($runnerData);
        $metrics = $this->_helper->getMetrics();
        $context = $this->_helper->getContext();

        $metrics->startClubsTime();
        $runner = $this->_runners
            ->createRunnerIfNotExists($context->getEventId(), $context->getStageId(), $runnerData, $class);
        $metrics->endClubsTime();

        $results = $runnerData['runner_results'] ?? [];
        if (!$results) {
            $metrics->setWarning('Runner without runner_results');
        }
        foreach ($results as $resultData) {
            $metrics->addOneRunnerResultToCounter();
            $runner = $this->_results->importInto($runner, $resultData);
        }

        $metrics->startClubsTime();
        $runner = $this->_addClub($runner, $runnerData['club'] ?? null);
        $metrics->endClubsTime();

        $metrics->addToRunnerCounter(1);
        return $runner;
    }

    private function _withLegNumberFromFirstResult(array $runnerData): array
    {
        if (!isset($runnerData['leg_number']) && isset($runnerData['runner_results'][0]['leg_number'])) {
            $runnerData['leg_number'] = $runnerData['runner_results'][0]['leg_number'];
        }
        return $runnerData;
    }

    private function _addClub(Runner $runner, ?array $club): Runner
    {
        if (!$club || $this->_helper->isArrayWithoutValues($club)) {
            return $runner;
        }
        $context = $this->_helper->getContext();
        $created = $this->_clubs->createIfNotExists($context->getEventId(), $context->getStageId(), $club);
        return $runner->addClub($created);
    }
}
