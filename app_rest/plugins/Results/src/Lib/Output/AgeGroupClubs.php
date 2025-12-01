<?php

declare(strict_types = 1);

namespace Results\Lib\Output;

use Rankings\Lib\Summarization\AgeGroupDefinitions;
use Rankings\Lib\Summarization\ClubList;
use Rankings\Lib\Summarization\RunnersByClub;
use Cake\Http\Response;
use RestApi\Lib\RestRenderer;

class AgeGroupClubs implements RestRenderer
{
    private ClubList $_results;

    public function setResults(array $results): static
    {
        $ageGroupDefinitions = new AgeGroupDefinitions();
        $this->_results = new ClubList();
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
