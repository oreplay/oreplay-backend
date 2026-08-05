<?php

declare(strict_types = 1);

namespace Rankings\Lib\ScoringAlgorithms;

use Rankings\Model\Entity\RankingOrganizer;
use Rankings\Model\Table\ParticipantInterface;
use Results\Model\Entity\Overalls;

interface ScoringAlgorithm
{
    public const int NEEDS_POSITION = -1;

    public function participantScore(ParticipantInterface $participant, ?ParticipantInterface $leader): ?float;
    public function calculateOverallScore(Overalls $overalls): Overalls;

    /**
     * @param RankingOrganizer $newOrganizer
     * @param RankingOrganizer[] $existingRankingOrganizerArray
     * @throws OrganizerLimitException when adding the organizer would exceed the allowed amount
     */
    public function limitOrganizers(RankingOrganizer $newOrganizer, array $existingRankingOrganizerArray = []): void;
}
