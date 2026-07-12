<?php

declare(strict_types = 1);

namespace Rankings\Test\TestCase\Lib\Rbac;

use App\Model\Entity\User;
use Cake\TestSuite\TestCase;
use Rankings\Lib\Rbac\RankingMembershipInterface;
use Rankings\Lib\Rbac\RankingsScopeContributor;
use Rankings\Lib\Rbac\StubRankingMembership;

class RankingsScopeContributorTest extends TestCase
{
    private function membership(bool $isManager): RankingMembershipInterface
    {
        return new class ($isManager) implements RankingMembershipInterface {
            public function __construct(private bool $isManager)
            {
            }

            public function isManager(User $user): bool
            {
                return $this->isManager;
            }
        };
    }

    public function testContribute_whenManager_grantsRankingsWildcard(): void
    {
        $contributor = new RankingsScopeContributor($this->membership(true));
        $this->assertSame(['rankings:*'], $contributor->contribute(new User(['is_admin' => false])));
    }

    public function testContribute_whenNotManager_grantsNothing(): void
    {
        $contributor = new RankingsScopeContributor($this->membership(false));
        $this->assertSame([], $contributor->contribute(new User(['is_admin' => false])));
    }

    public function testStubMembership_currentlyGrantsNoOne(): void
    {
        $this->assertFalse((new StubRankingMembership())->isManager(new User(['is_admin' => false])));
    }
}
