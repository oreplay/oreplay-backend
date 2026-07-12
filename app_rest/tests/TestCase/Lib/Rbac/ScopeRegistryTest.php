<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Lib\Rbac;

use App\Lib\Rbac\ScopeContributorInterface;
use App\Lib\Rbac\ScopeRegistry;
use App\Model\Entity\User;
use Cake\TestSuite\TestCase;

/**
 * Promotion candidate for freefri/cake-rest-api
 */
class ScopeRegistryTest extends TestCase
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

    public function testAssemble_withNoContributors_returnsEmptyString(): void
    {
        $this->assertSame('', ScopeRegistry::instance()->assemble(new User()));
    }

    public function testAssemble_joinsGrantsWithSpace(): void
    {
        ScopeRegistry::instance()->add(new FakeMultiGrantContributor());
        $this->assertSame('results:* rankings:*', ScopeRegistry::instance()->assemble(new User()));
    }

    public function testAssemble_combinesDistinctContributorsInOrder(): void
    {
        $registry = ScopeRegistry::instance();
        $registry->add(new FakeResultsGrantContributor());
        $registry->add(new FakeRankingsGrantContributor());
        $this->assertSame('results:* rankings:*', $registry->assemble(new User()));
    }

    public function testAdd_isIdempotentPerContributorClass(): void
    {
        $registry = ScopeRegistry::instance();
        $registry->add(new FakeResultsGrantContributor());
        $registry->add(new FakeResultsGrantContributor());
        $this->assertSame('results:*', $registry->assemble(new User()));
    }
}

class FakeMultiGrantContributor implements ScopeContributorInterface
{
    public function contribute(User $user): array
    {
        return ['results:*', 'rankings:*'];
    }
}

class FakeResultsGrantContributor implements ScopeContributorInterface
{
    public function contribute(User $user): array
    {
        return ['results:*'];
    }
}

class FakeRankingsGrantContributor implements ScopeContributorInterface
{
    public function contribute(User $user): array
    {
        return ['rankings:*'];
    }
}
