<?php

declare(strict_types = 1);

namespace Results\Lib\Output;

use Results\Model\Entity\Runner;

class DuplicatedRunners
{
    public static function setResults(array $results, array $filters): array
    {
        $duplicatedRunners = [];
        /** @var Runner $currentRunner */
        foreach ($results as $currentRunner) {
            /** @var Runner $tmpRunner */
            foreach ($results as $tmpRunner) {
                $tmpRunnerArray = [
                    'db_id' => $tmpRunner->db_id,
                    'bib_number' => $tmpRunner->bib_number,
                ];
                $isSameDbId = $currentRunner->isSameField('db_id', $tmpRunnerArray);
                $isSameBib = $currentRunner->isSameField('bib_number', $tmpRunnerArray);
                $currentId = $currentRunner->db_id . '_' . $currentRunner->bib_number;
                $tmpId = $tmpRunner->db_id . '_' . $tmpRunner->bib_number;
                if ($currentRunner->id !== $tmpRunner->id) {
                    if ($isSameDbId || $isSameBib) {
                        $sameId = !isset($duplicatedRunners[$currentId]) && !isset($duplicatedRunners[$tmpId]);
                        if ($sameId) {
                            $duplicatedRunners[$currentId] = [$currentRunner, $tmpRunner];
                        }
                    }
                }
            }
        }
        //return $duplicatedRunners;
        $toReturn = [];
        foreach ($duplicatedRunners as $duplicatedRunnerPair) {
            /** @var Runner $runner */
            foreach ($duplicatedRunnerPair as $runner) {
                $toReturn[] = $runner;
                //$toReturn[] = $runner->toSimpleArray();
            }
        }
        return $toReturn;
    }
}
