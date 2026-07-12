<?php

declare(strict_types = 1);

namespace Rankings\Lib\Rbac;

use App\Model\Entity\User;

interface RankingMembershipInterface
{
    public function isManager(User $user): bool;
}
