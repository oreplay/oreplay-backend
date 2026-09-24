<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Results\Lib\OrganizerMatcher;
use Results\Model\Entity\Organizer;

class OrganizerMatcherTest extends TestCase
{
    private OrganizerMatcher $matcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->matcher = new OrganizerMatcher([
            $this->_organizer('monte', 'MONTE EL PARDO', 'Madrid', 'Madrid'),
            $this->_organizer('ferrol', 'MONTAÑA_FERROL', 'La Coruña', 'Ferrol'),
            $this->_organizer('compas', 'COMPÁS', 'Lugo', 'Lugo'),
            $this->_organizer('tjalve', 'TJALVE', 'Burgos', null),
            $this->_organizer('arnela', 'ADC ARNELA', 'La Coruña', 'Porto do Son'),
            $this->_organizer('cdcebe', 'C.D.C.E.B.E.', 'Madrid', null),
            $this->_organizer('corzo', 'CORZO', 'Burgos', null),
            $this->_organizer('cota', 'COTA', 'Madrid', null),
            $this->_organizer('fcoc', 'FCOC', null, null),
            $this->_organizer('murciao', 'MURCIA-O', 'Murcia', 'Murcia'),
            $this->_organizer('usc', 'USC', 'La Coruña', 'Santiago de Compostela'),
            $this->_organizer('trevinca', 'TREVINCA', 'Orense', 'O Barco de Valdeorras'),
            $this->_organizer('nordeste', 'NORDESTE-O', 'Asturias', 'Ribadesella'),
            $this->_organizer('cocan', 'COCAN', 'Santa Cruz de Tenerife', null),
            $this->_organizer('montsant', 'MONTSANT', 'Tarragona', null),
            $this->_organizer('adol', 'ADOL', 'Sevilla', null),
        ]);
    }

    private function _organizer(string $id, string $name, ?string $province, ?string $city): Organizer
    {
        return new Organizer([
            'id' => $id,
            'name' => $name,
            'province' => $province,
            'city' => $city,
        ]);
    }

    private function _assertMatches(string $expectedId, string $clubName): void
    {
        $organizer = $this->matcher->match($clubName);
        $this->assertNotNull($organizer, "\"$clubName\" should have matched $expectedId");
        $this->assertEquals($expectedId, $organizer->id, "\"$clubName\" matched the wrong organizer");
    }

    private function _assertRefuses(string $clubName): void
    {
        $organizer = $this->matcher->match($clubName);
        $name = $organizer ? $organizer->name : '';
        $this->assertNull($organizer, "\"$clubName\" must not be matched, but it resolved to \"$name\"");
    }

    public function testMatchesTheExactName()
    {
        $this->_assertMatches('monte', 'MONTE EL PARDO');
        $this->_assertMatches('corzo', 'CORZO');
    }

    public function testUnderscoresAndDashesAreReadAsSpaces()
    {
        $this->_assertMatches('monte', 'MONTE_EL_PARDO');
        $this->_assertMatches('ferrol', 'MONTAÑA_FERROL');
        $this->_assertMatches('murciao', 'MURCIA O');
    }

    public function testAccentsAreIgnored()
    {
        $this->_assertMatches('compas', 'COMPÁS');
        $this->_assertMatches('compas', 'COMPAS');
        $this->_assertMatches('ferrol', 'MONTAÑA FERROL');
        $this->_assertMatches('ferrol', 'MONTANA FERROL');
    }

    public function testDotsInsideAnAbbreviationAreIgnored()
    {
        $this->_assertMatches('cdcebe', 'C.D.C.E.B.E.');
        $this->_assertMatches('cdcebe', 'CDCEBE');
        $this->_assertMatches('cdcebe', 'Madrid C.D.C.E.B.E.');
    }

    public function testTrimsALeadingProvince()
    {
        $this->_assertMatches('monte', 'Madrid MONTE_EL_PARDO');
        $this->_assertMatches('compas', 'LUGO COMPÁS');
        $this->_assertMatches('tjalve', 'Burgos TJALVE');
        $this->_assertMatches('arnela', 'La Coruña ADC_ARNELA');
    }

    public function testTrimsATrailingProvinceOrCity()
    {
        $this->_assertMatches('compas', 'COMPÁS LUGO');
        $this->_assertMatches('ferrol', 'MONTAÑA_FERROL Ferrol');
        $this->_assertMatches('arnela', 'ADC ARNELA Porto do Son');
    }

    public function testTrimsACityThatIsAlsoPartOfTheName()
    {
        $this->_assertMatches('ferrol', 'Ferrol MONTAÑA_FERROL');
        $this->_assertMatches('ferrol', 'FERROL MONTAÑA FERROL');
    }

    public function testTrimsARegionName()
    {
        $this->_assertMatches('corzo', 'CASTILLA Y LEÓN CORZO');
        $this->_assertMatches('cota', 'Comunidad de Madrid COTA');
    }

    public function testTrimsAPlaceTruncatedToSixteenCharactersByTheUploadingSoftware()
    {
        $this->_assertMatches('cota', 'COMUNIDAD DE MAD COTA');
        $this->_assertMatches('nordeste', 'PRINCIPADO DE AS NORDESTE-O');
        $this->_assertMatches('usc', 'Santiago de Comp USC');
        $this->_assertMatches('trevinca', 'O Barco de Valde TREVINCA');
    }

    public function testTrimsOnlyTheSixteenCharacterCutNotAnyShorterPrefix()
    {
        $this->_assertRefuses('Santiago de USC');
        $this->_assertRefuses('COMUNIDAD DE COTA');
    }

    public function testTrimsTenerifeAsAnAliasOfItsProvince()
    {
        $this->_assertMatches('cocan', 'Tenerife COCAN');
        $this->_assertMatches('cocan', 'COCAN TENERIFE');
    }

    public function testRefusesARegionAbbreviationOrCountryPrefix()
    {
        $this->_assertMatches('montsant', 'MONTSANT');
        $this->_assertRefuses('CAT MONTSANT');
        $this->_assertMatches('adol', 'ADOL');
        $this->_assertRefuses('España Adol');
    }

    public function testRefusesAnyLeftoverThatIsNotAPlace()
    {
        $this->_assertRefuses('MONTE EL PARDO MX');
        $this->_assertRefuses('Tjalve OK');
        $this->_assertRefuses('Sweden TJALVE OK');
        $this->_assertRefuses('AD Tjalve');
    }

    public function testRefusesTeamNamesUsedInTheClubField()
    {
        $this->_assertRefuses('Arnela 50 e pico');
        $this->_assertRefuses('Arnela 50 e pico [G]');
        $this->_assertRefuses('Arnela 50 e pico [G] (ADC_ARNELA)');
        $this->_assertRefuses('Arnelos');
        $this->_assertRefuses('MONTE EL PARDO MX');
    }

    public function testRefusesAFederationQualifiedByAMemberClub()
    {
        $this->_assertMatches('fcoc', 'FCOC');
        $this->_assertRefuses('FCOC (UPC)');
        $this->_assertRefuses('FCOC (COC)');
    }

    public function testRefusesPlaceholdersAndBarePlaces()
    {
        $this->_assertRefuses('INDEPENDIENTE');
        $this->_assertRefuses('Sin club');
        $this->_assertRefuses('MURCIA');
        $this->_assertRefuses('La Coruña');
        $this->_assertRefuses('');
    }

    public function testRefusesWhenTwoOrganizersShareTheSameName()
    {
        $ambiguous = new OrganizerMatcher([
            $this->_organizer('first', 'COMPÁS', 'Lugo', null),
            $this->_organizer('second', 'COMPAS', 'Madrid', null),
        ]);
        $this->assertNull($ambiguous->match('COMPAS'), 'a name shared by two organizers must not resolve');
    }
}
