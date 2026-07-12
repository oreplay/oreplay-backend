<?php

declare(strict_types = 1);

namespace Rankings\Lib\Rbac;

use App\Model\Entity\User;

class StubRankingMembership implements RankingMembershipInterface
{
    public function isManager(User $user): bool
    {
        // Placeholder until per-user ranking assignment exists
        return false;
    }
}
