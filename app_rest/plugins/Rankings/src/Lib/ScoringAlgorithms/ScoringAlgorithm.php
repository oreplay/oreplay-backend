<?php

declare(strict_types = 1);

namespace Rankings\Lib\ScoringAlgorithms;

use Rankings\Model\Table\ParticipantInterface;
use Results\Model\Entity\PartialOverall;

interface ScoringAlgorithm
{
    public const int NEEDS_POSITION = -1;

    public function participantScore(ParticipantInterface $participant, ?ParticipantInterface $leader): ?float;
    public function calculateOverallScore(array $parts): PartialOverall;
}
