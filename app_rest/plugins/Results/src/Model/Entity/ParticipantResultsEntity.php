<?php

declare(strict_types = 1);

namespace Results\Model\Entity;

interface ParticipantResultsEntity
{
    public function getId(): string;
    public function isSameResult(RunnerResult $runnerResultToSave): bool;
    public function hasSameSplits(array $compareArray): bool;
    public function setHash(array $resultData);
    public function addSplit(Split $split);
}
