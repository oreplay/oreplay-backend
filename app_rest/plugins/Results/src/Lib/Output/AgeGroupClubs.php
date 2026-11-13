<?php

declare(strict_types = 1);

namespace Results\Lib\Output;

use Cake\Http\Response;
use Rankings\Lib\Summarization\Lvl1ClubList;
use Rankings\Lib\ScoringAlgorithms\ScoringAlgorithm;
use Rankings\Lib\Summarization\Settings\AgeGroupDefinitions;
use RestApi\Lib\RestRenderer;

class AgeGroupClubs implements RestRenderer
{
    private Lvl1ClubList $_results;
    private ScoringAlgorithm $_calculator;

    public function __construct(ScoringAlgorithm $calculator)
    {
        $this->_calculator = $calculator;
    }

    public function setResults(array $results): static
    {
        $ageGroupDefinitions = new AgeGroupDefinitions($this->_calculator);
        $this->_results = new Lvl1ClubList();
        foreach ($results as $runnersList) {
            foreach ($runnersList as $runner) {
                $this->_results->addRunner($runner, $ageGroupDefinitions);
            }
        }
        return $this;
    }

    public function setHeadersForDownload(Response $response, $title = null): Response
    {
        if ($title == null) {
            $title = 'AgeGroupClubs.csv';
        }
        $response = $response->withType('csv');
        $response = $response->withDisabledCache();
        return $response->withDownload($title);
    }

    public function render()
    {
        $clubs = $this->_results->getOrderedByPointsPerClub();
        $toRet = [];
        foreach ($clubs as $club) {
            if (!$toRet) {
                $toRet[] = $club->getCsvHeader();
            }
            $toRet[] = implode("\n", $club->toCsvRows());
        }
        return implode("\n", $toRet);
    }
}
