<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Club;
use Results\Model\Entity\Event;
use Results\Model\Entity\Stage;
use Results\Model\Table\ClubsTable;
use Results\Model\Table\OrganizersTable;
use Results\Test\Fixture\ClubsFixture;
use Results\Test\Fixture\EventsFixture;
use Results\Test\Fixture\OrganizersFixture;
use Results\Test\Fixture\StagesFixture;

class StageClubsControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        EventsFixture::LOAD,
        StagesFixture::LOAD,
        ClubsFixture::LOAD,
        OrganizersFixture::LOAD,
    ];

    public function setUp(): void
    {
        parent::setUp();
        OrganizersTable::load()->deleteMatcherCache();
    }

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/events/' . Event::FIRST_EVENT . '/stages/'
            . Stage::FIRST_STAGE . '/clubs/';
    }

    private function _insertClub(string $shortName): void
    {
        $Clubs = ClubsTable::load();
        $club = $Clubs->fillNewWithStage(['short_name' => $shortName], Event::FIRST_EVENT, Stage::FIRST_STAGE);
        $Clubs->saveOrFail($club);
    }

    private function _clubNamed(array $data, string $shortName): array
    {
        foreach ($data as $club) {
            if ($club['short_name'] === $shortName) {
                return $club;
            }
        }
        $this->fail("no club named \"$shortName\" in the response");
    }

    public function testGetList()
    {
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $expected = [
            [
                '_c' => Club::class,
                'id' => ClubsFixture::CLUB_1,
                'short_name' => 'Club A'
            ]
        ];
        $this->assertEquals($expected, $bodyDecoded['data']);
    }

    public function testGetListIncludesRegionProvinceAndCityOfTheMatchedOrganizer()
    {
        $this->_insertClub('Valencia ADCON');

        $this->get($this->_getEndpoint() . '?include=club.region,club.province,club.city');

        $bodyDecoded = $this->assertJsonResponseOK();
        $club = $this->_clubNamed($bodyDecoded['data'], 'Valencia ADCON');
        $this->assertEquals('Comunidad Valenciana', $club['region'], 'the region name comes from region_code');
        $this->assertEquals('Valencia', $club['province']);
        $this->assertEquals('Requena', $club['city']);
    }

    public function testMatchesAClubNameQualifiedByTheOrganizerCityNotOnlyItsProvince()
    {
        $this->_insertClub('ADCON Requena');

        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?include=club.province,club.city');

        $bodyDecoded = $this->assertJsonResponseOK();
        $club = $this->_clubNamed($bodyDecoded['data'], 'ADCON Requena');
        $this->assertEquals('Valencia', $club['province'], 'the city feeds the trimmable places too');
        $this->assertEquals('Requena', $club['city']);
    }

    public function testGetListIncludesOnlyTheRequestedFields()
    {
        $this->_insertClub('ADCON');

        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?include=club.city');

        $bodyDecoded = $this->assertJsonResponseOK();
        $club = $this->_clubNamed($bodyDecoded['data'], 'ADCON');
        $this->assertEquals('Requena', $club['city']);
        $this->assertArrayNotHasKey('region', $club);
        $this->assertArrayNotHasKey('province', $club);
    }

    public function testGetListReturnsNullsForAClubThatMatchesNoOrganizer()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?include=club.region,club.province,club.city');

        $bodyDecoded = $this->assertJsonResponseOK();
        $club = $this->_clubNamed($bodyDecoded['data'], 'Club A');
        $this->assertNull($club['region'], 'an unmatched club keeps the field present and empty');
        $this->assertNull($club['province']);
        $this->assertNull($club['city']);
    }

    public function testGetListWithoutIncludeAddsNoOrganizerFields()
    {
        $this->_insertClub('ADCON');

        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint());

        $bodyDecoded = $this->assertJsonResponseOK();
        $club = $this->_clubNamed($bodyDecoded['data'], 'ADCON');
        $this->assertArrayNotHasKey('region', $club);
        $this->assertArrayNotHasKey('province', $club);
        $this->assertArrayNotHasKey('city', $club);
    }

    public function testGetListRejectsAnUnknownInclude()
    {
        $this->skipNextRequestInSwagger();
        $this->get($this->_getEndpoint() . '?include=club.population');

        $this->assertExceptionMessage('Cannot include club.population in clubs', 400);
    }
}
