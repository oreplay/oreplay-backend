<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

interface ParticipantResultsEntity
{
    public function isSameResult(RunnerResult $runnerResultToSave): bool;
}
