<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Model\Entity;

use App\Lib\Rbac\ScopeContributorInterface;
use App\Lib\Rbac\ScopeRegistry;
use App\Model\Entity\User;
use Cake\TestSuite\TestCase;

class UserTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        ScopeRegistry::instance()->clear();
    }

    public function tearDown(): void
    {
        ScopeRegistry::instance()->clear();
        parent::tearDown();
    }

    public function testScope_admin_returnsFullWildcard(): void
    {
        $user = new User(['is_admin' => true]);
        $this->assertSame('*', $user->scope);
    }

    public function testScope_nonAdmin_returnsAssembledRegistryGrants(): void
    {
        ScopeRegistry::instance()->add(new class implements ScopeContributorInterface {
            public function contribute(User $user): array
            {
                return ['results:*'];
            }
        });
        $user = new User(['is_admin' => false]);
        $this->assertSame('results:*', $user->scope);
    }

    public function testScope_nonAdmin_withNoContributors_returnsEmptyString(): void
    {
        $user = new User(['is_admin' => false]);
        $this->assertSame('', $user->scope);
    }
}
