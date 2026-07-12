<?php

declare(strict_types = 1);

namespace App\Lib\Rbac;

use App\Model\Entity\User;

/**
 * Promotion candidate for freefri/cake-rest-api: generic, no plugin-specific knowledge. Blocked only
 * by the App\Model\Entity\User dependency, which must be abstracted (interface) before the move.
 */
class ScopeRegistry
{
    private static ?ScopeRegistry $instance = null;

    /** @var array<class-string, ScopeContributorInterface> */
    private array $contributors = [];

    private function __construct()
    {
    }

    public static function instance(): ScopeRegistry
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Keyed by class so a plugin's bootstrap re-running per request (IntegrationTestTrait) does not
     * duplicate its grants.
     */
    public function add(ScopeContributorInterface $contributor): void
    {
        $this->contributors[$contributor::class] = $contributor;
    }

    public function assemble(User $user): string
    {
        $grants = [];
        foreach ($this->contributors as $contributor) {
            foreach ($contributor->contribute($user) as $grant) {
                $grants[] = $grant;
            }
        }
        return implode(' ', $grants);
    }

    public function clear(): void
    {
        $this->contributors = [];
    }
}
