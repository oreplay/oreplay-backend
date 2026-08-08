<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Lib\UploadMetrics;
use Results\Model\Entity\ClassEntity;
use Results\Model\Entity\Team;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\RunnersTable;
use Results\Model\Table\TeamResultsTable;
use Results\Model\Table\TeamsTable;

class TeamImporter
{
    private TeamsTable $_teams;
    private ClubsTable $_clubs;
    private TeamResultImporter $_results;
    private RunnerImporter $_runners;
    private UploadHelper $_helper;

    public function __construct(TeamsTable $teams, UploadHelper $helper)
    {
        $this->_teams = $teams;
        /** @var ClubsTable $clubs */
        $clubs = $teams->Clubs->getTarget();
        $this->_clubs = $clubs;
        /** @var TeamResultsTable $teamResults */
        $teamResults = $teams->TeamResults->getTarget();
        $this->_results = new TeamResultImporter($teamResults, $helper);
        /** @var RunnersTable $runners */
        $runners = $teams->Runners->getTarget();
        $this->_runners = new RunnerImporter($runners, $helper);
        $this->_helper = $helper;
    }

    public static function getMissingLegs(mixed $runners, mixed $results): array
    {
        $totalResults = count($runners);
        $hasSameRunnersAsResults = count($runners) && $totalResults;
        if (!$hasSameRunnersAsResults) {
            return [];
        }
        $legs = [];
        foreach ($results as $current) {
            $currentLeg = $current['leg_number'] ?? null;
            if ($currentLeg) {
                $legs[$currentLeg] = $currentLeg;
            }
        }
        $missingLegs = [];
        for ($i = 1; $i <= $totalResults; $i++) {
            if (!isset($legs[$i])) {
                $missingLegs[] = $i;
            }
        }
        return $missingLegs;
    }

    public function import(array $teamData, ClassEntity $class): Team
    {
        $metrics = $this->_helper->getMetrics();
        $context = $this->_helper->getContext();

        $team = $metrics->measure(UploadMetrics::CLUBS, fn() => $this->_teams
            ->createTeamIfNotExists($context->getEventId(), $context->getStageId(), $teamData, $class));

        $results = $teamData['team_results'] ?? [];
        if (!$results) {
            $metrics->setWarning('Team without team_results');
        }
        $runners = $teamData['runners'] ?? [];
        $missingLegs = self::getMissingLegs($runners, $results);
        foreach ($results as $resultData) {
            $resultData = $this->_withLegNumberWhenNotFinished($resultData, $missingLegs);
            $metrics->addOneTeamResultToCounter();
            $team = $this->_results->importInto($team, $resultData);
        }

        if (!$runners) {
            $metrics->setWarning('Team without runners ' . ($teamData['team_name'] ?? ''));
        }
        foreach ($runners as $runnerData) {
            $team->addRunner($this->_runners->import($runnerData, $this->_classOfTeamRunners()));
        }

        $team = $metrics->measure(
            UploadMetrics::CLUBS,
            fn() => $this->_addClub($team, $teamData['club'] ?? null)
        );

        $metrics->addToTeamCounter(1);
        return $team;
    }

    private function _classOfTeamRunners(): ClassEntity
    {
        $noClass = new ClassEntity();
        $noClass->id = null;
        return $noClass;
    }

    private function _withLegNumberWhenNotFinished(array $resultData, array &$missingLegs): array
    {
        $isRelayLegNotFinished = $missingLegs
            && !($resultData['time_seconds'] ?? null)
            && !($resultData['points_final'] ?? null)
            && !($resultData['leg_number'] ?? null);
        if ($isRelayLegNotFinished) {
            $resultData['leg_number'] = array_pop($missingLegs);
        }
        return $resultData;
    }

    private function _addClub(Team $team, ?array $club): Team
    {
        if (!$club) {
            return $team;
        }
        $context = $this->_helper->getContext();
        $created = $this->_clubs->createIfNotExists($context->getEventId(), $context->getStageId(), $club);
        return $team->addClub($created);
    }
}
