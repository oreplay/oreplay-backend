<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

/**
 * Stations read by a radio during one upload, the same fact splits carry as is_intermediate.
 *
 * Must stay an object: UploadHelper::inClass() shallow-clones the helper, so an array property
 * would be copied per class and the stations of every class but the last would be lost.
 */
class IntermediateStations
{
    private array $_stations = [];

    public function add(string $station): void
    {
        $this->_stations[$station] = $station;
    }

    /**
     * @return string[]
     */
    public function toList(): array
    {
        return array_values($this->_stations);
    }
}
