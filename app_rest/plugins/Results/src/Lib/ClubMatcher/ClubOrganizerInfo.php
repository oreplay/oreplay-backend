<?php

declare(strict_types = 1);

namespace Results\Lib\ClubMatcher;

use Results\Model\Entity\Club;
use Results\Model\Table\OrganizersTable;

class ClubOrganizerInfo
{
    public function __construct(private readonly OrganizersTable $organizers)
    {
    }

    /**
     * @param Club[] $clubs
     * @return Club[]
     */
    public function addTo(array $clubs, IncludedOrganizerFields $fields): array
    {
        if ($fields->areEmpty()) {
            return $clubs;
        }
        $matcher = $this->organizers->getCachedMatcher();
        foreach ($clubs as $club) {
            $club->addOrganizerInfo($matcher->match((string)$club->short_name), $fields->names());
        }
        return $clubs;
    }
}
