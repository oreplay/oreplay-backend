<?php

declare(strict_types = 1);

namespace Results\Lib\Import;

use Results\Lib\UploadHelper;
use Results\Model\Entity\ClassEntity;
use Results\Model\Table\ClassesTable;

class DeclaredRadiosImporter
{
    public function __construct(
        private readonly ClassesTable $classes,
        private readonly UploadHelper $helper
    ) {
    }

    public function import(array $classArray, ClassEntity $class): void
    {
        $stations = self::_declaredStationsIn($classArray);
        if (!$stations || !$this->helper->getChecker()->isIntermediates()) {
            return;
        }
        $this->classes->storeDeclaredRadios($class, $stations);
        foreach ($stations as $station) {
            $this->helper->getIntermediateStations()->add($station);
        }
    }

    /**
     * @return string[]
     */
    private static function _declaredStationsIn(array $classArray): array
    {
        $stations = [];
        foreach ($classArray['classes_controls'] ?? [] as $classControl) {
            $station = (string)($classControl['control']['station'] ?? '');
            if ($station !== '') {
                $stations[] = $station;
            }
        }
        return $stations;
    }
}
