<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\Rbac;

use App\Model\Entity\User;
use Cake\TestSuite\TestCase;
use Results\Lib\Rbac\ResultsScopeContributor;

class ResultsScopeContributorTest extends TestCase
{
    public function testContribute_grantsResultsWildcardToAnyUser(): void
    {
        $contributor = new ResultsScopeContributor();
        $this->assertSame(['results:*'], $contributor->contribute(new User(['is_admin' => false])));
    }
}
