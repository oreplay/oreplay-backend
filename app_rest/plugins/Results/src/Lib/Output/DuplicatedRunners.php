<?php

declare(strict_types = 1);

namespace Results\Lib\Output;

use Results\Model\Entity\Runner;
use Results\Model\Table\RunnersTable;

class DuplicatedRunners
{
    public const PARAM_REMOVE_FROM_RANKING = 'remove_from_ranking_runner_id';

    public static function setResults(array $results, array $filters): array
    {
        $toRemove = null;
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
                            $removed = self::removeFromRanking($filters, $tmpRunner, $currentRunner);
                            if ($removed) {
                                $toRemove = $currentId;
                            }
                            $duplicatedRunners[$currentId] = [$currentRunner, $tmpRunner];
                        }
                    }
                }
            }
        }
        if ($toRemove) {
            unset($duplicatedRunners[$toRemove]);
        }
        //return $duplicatedRunners;
        $toReturn = [];
        foreach ($duplicatedRunners as $duplicatedRunnerPair) {
            /** @var Runner $runner */
            foreach ($duplicatedRunnerPair as $runner) {
                //$toReturn[] = $runner;
                $toReturn[] = $runner->toSimpleDeduplicationArray();
            }
        }
        return $toReturn;
    }

    private static function removeFromRanking(array $filters, Runner $tmpRunner, Runner $currentRunner): bool
    {
        $bad = $filters[DuplicatedRunners::PARAM_REMOVE_FROM_RANKING] ?? null;
        if ($tmpRunner->id === $bad) {
            $note = $currentRunner->class->short_name;
            RunnersTable::load()->removeFromRanking($tmpRunner->id, $note);
            return true;
        } else if ($currentRunner->id === $bad) {
            $note = $tmpRunner->class->short_name;
            RunnersTable::load()->removeFromRanking($currentRunner->id, $note);
            return true;
        } else {
            return false;
        }
    }
}
