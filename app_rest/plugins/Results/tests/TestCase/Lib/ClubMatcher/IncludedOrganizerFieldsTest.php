<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib\ClubMatcher;

use Cake\TestSuite\TestCase;
use RestApi\Lib\Exception\DetailedException;
use Results\Lib\ClubMatcher\IncludedOrganizerFields;

class IncludedOrganizerFieldsTest extends TestCase
{
    public function testNothingIsIncludedWhenTheParameterIsAbsent()
    {
        foreach ([null, '', '   '] as $queryValue) {
            $included = IncludedOrganizerFields::fromQueryValue($queryValue);
            $this->assertTrue($included->areEmpty(), 'an absent parameter must include nothing');
            $this->assertEquals([], $included->names());
        }
    }

    public function testTranslatesEachIncludeIntoAnOrganizerFieldName()
    {
        $included = IncludedOrganizerFields::fromQueryValue('club.region,club.province,club.city');

        $this->assertFalse($included->areEmpty());
        $this->assertEquals(['region', 'province', 'city'], $included->names());
    }

    public function testKeepsTheRequestedOrder()
    {
        $included = IncludedOrganizerFields::fromQueryValue('club.city,club.region');

        $this->assertEquals(['city', 'region'], $included->names());
    }

    public function testIgnoresSpacesAroundEachInclude()
    {
        $included = IncludedOrganizerFields::fromQueryValue(' club.region , club.city ');

        $this->assertEquals(['region', 'city'], $included->names());
    }

    public function testRejectsAnUnknownInclude()
    {
        $this->expectException(DetailedException::class);
        $this->expectExceptionMessage('Cannot include club.population in clubs');

        IncludedOrganizerFields::fromQueryValue('club.region,club.population');
    }

    public function testRejectsAFieldNameThatIsNotPrefixedAsAClubField()
    {
        $this->expectExceptionMessage('Cannot include region in clubs');

        IncludedOrganizerFields::fromQueryValue('region');
    }
}
