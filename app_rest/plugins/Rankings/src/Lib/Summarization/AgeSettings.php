<?php

declare(strict_types = 1);

namespace Rankings\Lib\Summarization;

class AgeSettings
{
    private mixed $ageGroup;
    private mixed $classRatio;
    private mixed $amountOfRunners;

    public function __construct($ageGroup, $classRatio, $amountOfRunners)
    {
        $this->ageGroup = $ageGroup;
        $this->classRatio = $classRatio;
        $this->amountOfRunners = $amountOfRunners;
    }

    public function getAgeGroup()
    {
        return $this->ageGroup;
    }

    public function getClassRatio()
    {
        return $this->classRatio;
    }

    public function getAmountOfRunners()
    {
        return $this->amountOfRunners;
    }
}
