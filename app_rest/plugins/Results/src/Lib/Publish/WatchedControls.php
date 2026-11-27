<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

use App\Lib\Consts\CacheGrp;
use Cake\Cache\Cache;

class WatchedControls
{
    public function watch(string $stageId, string $station): void
    {
        $this->watchAll($stageId, [$station]);
    }

    /**
     * @param string[] $stations
     */
    public function watchAll(string $stageId, array $stations): void
    {
        $entries = [];
        foreach ($stations as $station) {
            $entries[$this->_keyOf($stageId, $station)] = true;
        }
        if ($entries) {
            Cache::writeMany($entries, CacheGrp::LIVE_CONTROLS);
        }
    }

    /**
     * @param string[] $stations
     * @return string[]
     */
    public function watchedAmong(string $stageId, array $stations): array
    {
        if (!$stations) {
            return [];
        }
        $stationsByKey = [];
        foreach ($stations as $station) {
            $stationsByKey[$this->_keyOf($stageId, $station)] = $station;
        }
        $watched = [];
        foreach (Cache::readMany(array_keys($stationsByKey), CacheGrp::LIVE_CONTROLS) as $key => $isWatched) {
            if ($isWatched) {
                $watched[] = $stationsByKey[$key];
            }
        }
        return $watched;
    }

    private function _keyOf(string $stageId, string $station): string
    {
        return md5($stageId . '/' . $station);
    }
}
