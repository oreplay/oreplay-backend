<?php

declare(strict_types = 1);

namespace Results\Lib\Output;

use Cake\Http\Response;
use RestApi\Lib\RestRenderer;

class ReadablePointsCsv implements RestRenderer
{
    private array $_results;

    public function setHeadersForDownload(Response $response, $title = null): Response
    {
        if ($title == null) {
            $title = 'ReadablePoints.csv';
        }
        $response = $response->withType('csv');
        $response = $response->withDisabledCache();
        return $response->withDownload($title);
    }

    public function render()
    {
        $toReturn = '';
        $resultsArray = json_decode(json_encode($this->_results), true);
        foreach ($resultsArray as $classGroup) {
            $toReturn .= $this->_convertToCsv($classGroup);
        }
        return $toReturn;
    }

    public function setResults(array $results): static
    {
        $isAssociativeArray = isset($results[0]);
        if ($isAssociativeArray) {
            // convert associative array to a map
            $results = ['short_class' => $results];
        }
        $this->_results = $results;
        return $this;
    }

    private function _convertToCsv(array $runnersList): string
    {
        $toRet = '';
        $parts = '';
        $head = '';

        foreach ($runnersList as $runner) {
            $stageCounter = 1;

            foreach ($runner['overalls']['parts'] as $part) {
                $x = $part['stage_order'] - $stageCounter;

                if ($toRet === '') {
                    if ($stageCounter === 1) {
                        $head .= 'Pos;Name;Club;Pts;';
                    }
                    for ($i = 0; $i < $x; $i++) {
                        $head .= "Stage {$stageCounter};;";
                    }
                    $head .= ($part['stage']['description'] ?? '') . ';;';
                }

                for ($i = 0; $i < $x; $i++) {
                    $stageCounter++;
                    $parts .= "-;;";
                }

                $array = [];

                if (!($part['contributory'] ?? null)) {
                    $array[] = '(not contributory)';
                }

                if (!empty($part['note'])) {
                    $array[] = $part['note'];
                }

                $parts .= "{$part['points_final']};" . implode(' ', $array) . ";";
                $stageCounter++;
            }

            if ($toRet === '') {
                $toRet .= $head . "\n";
                $toRet .= $runner['class']['short_name'] . "\n";
            }

            $toRet .= "{$runner['overalls']['overall']['position']};"
                . "{$runner['full_name']};"
                . "{$runner['club']['short_name']};"
                . "{$runner['overalls']['overall']['points_final']};"
                . "{$parts}\n";

            $parts = '';
        }
        return $toRet;
    }
}
