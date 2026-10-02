<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

use Results\Lib\UploadHelper;

/**
 * @property mixed $oe_key
 * @property string $short_name
 * @property string $long_name
 * @property ?string $radio_stations
 * @property string $event_id
 * @property string $stage_id
 * @property Runner[] $runners
 * @property Split[] $splits
 * @property Team[] $teams
 * @property Course $course
 */
class ClassEntity extends AppEntity
{
    use UploadHashTrait;

    public const ME = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a57';
    public const FE = 'd8a87faf-68a4-487b-8f28-6e0ead6c1a56';

    private const RADIO_STATION_SEPARATOR = ',';

    protected array $_accessible = [
        '*' => false,
        'id' => false,
        'short_name' => true,
        'long_name' => true,
        'oe_key' => true,
    ];

    protected array $_virtual = [
    ];

    protected array $_hidden = [
        'event_id',
        'stage_id',
        'course_id',
        'uuid',
        'oe_key',
        'radio_stations',
        'upload_hash',
        'created',
        'modified',
        'deleted',
    ];

    public function addRunners(array $runners)
    {
        $this->runners = $runners;
    }

    /**
     * @param string[] $stations
     */
    public function declareRadioStations(array $stations): void
    {
        $this->radio_stations = implode(self::RADIO_STATION_SEPARATOR, $stations);
    }

    /**
     * @return string[]
     */
    public function getDeclaredRadioStations(): array
    {
        if (!$this->radio_stations) {
            return [];
        }
        return explode(self::RADIO_STATION_SEPARATOR, $this->radio_stations);
    }

    public function isShortNameIn(array $classNames): bool
    {
        return in_array($this->short_name, $classNames, true);
    }
}
