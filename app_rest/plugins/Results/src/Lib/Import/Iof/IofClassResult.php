<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

/**
 * One ClassResult, with the radio list that only exists as an XML comment beside it. They travel
 * together because the comment names the class's radios and a runner is judged against that list.
 */
class IofClassResult
{
    /**
     * @param string[] $radioStations
     */
    public function __construct(private readonly array $data, private readonly array $radioStations)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string[] empty for every producer but SportSoftware, which is not evidence that the class
     *                  has no radios
     */
    public function getRadioStations(): array
    {
        return $this->radioStations;
    }
}
