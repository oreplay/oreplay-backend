<?php

declare(strict_types = 1);

namespace App\Lib\Rbac;

use App\Model\Entity\User;

/**
 * Promotion candidate for freefri/cake-rest-api: generic, no plugin-specific knowledge. Blocked only
 * by the App\Model\Entity\User dependency, which must be abstracted (interface) before the move.
 */
interface ScopeContributorInterface
{
    /**
     * @return string[] Grant strings this contributor grants the user (RBAC format), or [].
     */
    public function contribute(User $user): array;
}
