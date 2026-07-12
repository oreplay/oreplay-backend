<?php

declare(strict_types = 1);

namespace Results\Lib\Rbac;

use App\Lib\Rbac\ScopeContributorInterface;
use App\Model\Entity\User;

class ResultsScopeContributor implements ScopeContributorInterface
{
    public function contribute(User $user): array
    {
        return ['results:*'];
    }
}
