<?php

declare(strict_types = 1);

namespace Results\Lib\Publish;

class ControlChangedPublisher
{
    public const string SOURCE_CPI = 'cpi';
    public const string SOURCE_UPLOAD = 'upload';

    public function __construct(
        private readonly ChannelPublisher $channel,
        private readonly WatchedControls $watched
    ) {
    }

    /**
     * @param array<?string> $stations
     */
    public function publish(string $stageId, array $stations, string $source, ?string $uploadId = null): void
    {
        $watchedStations = $this->watched->watchedAmong($stageId, array_unique(array_filter($stations)));
        foreach ($watchedStations as $station) {
            $this->channel->publish(
                'stage/' . $stageId . '/control/' . $station,
                ['station' => $station, 'source' => $source, 'uploadId' => $uploadId]
            );
        }
        $this->watched->watchAll($stageId, $watchedStations);
    }
}
