<?php

declare(strict_types = 1);

namespace Rankings\Lib\Rbac;

use App\Lib\Rbac\ScopeContributorInterface;
use App\Model\Entity\User;

class RankingsScopeContributor implements ScopeContributorInterface
{
    public function __construct(private RankingMembershipInterface $membership)
    {
    }

    public function contribute(User $user): array
    {
        if ($this->membership->isManager($user)) {
            return ['rankings:*'];
        }
        return [];
    }
}
