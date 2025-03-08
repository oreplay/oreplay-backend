<?php

declare(strict_types = 1);

namespace Results\Lib;

use Cake\Datasource\ResultSetInterface;
use Results\Model\Entity\Control;

trait UploadControlsTrait
{
    private array $_existingControls;

    public function setExistingControls(ResultSetInterface $existingRunnerResults): self
    {
        $this->_existingControls = [];
        /** @var Control $control */
        foreach ($existingRunnerResults as $control) {
            $this->storeControlByStation($control);
        }
        return $this;
    }

    public function getExistingControlByStation($stationNumber): ?Control
    {
        return $this->_existingControls[$stationNumber] ?? null;
    }

    public function storeControlByStation(Control $control): void
    {
        $stationNumber = $control->station;
        $this->_existingControls[$stationNumber] = $control;
    }
}
