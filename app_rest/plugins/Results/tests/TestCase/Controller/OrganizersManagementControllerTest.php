<?php

declare(strict_types = 1);

namespace Results\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\UsersFixture;
use App\Test\TestCase\Controller\ApiCommonErrorsTest;
use Results\Model\Entity\Organizer;
use Results\Model\Table\OrganizersTable;
use Results\Test\Fixture\OrganizersFixture;

class OrganizersManagementControllerTest extends ApiCommonErrorsTest
{
    protected array $fixtures = [
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD,
        OrganizersFixture::LOAD,
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/organizersManagement/';
    }

    private function _validCreateData(): array
    {
        return [
            'name' => 'NAVARRA-O',
            'country_code' => 'ES',
            'region_code' => 'NC',
        ];
    }

    private function _insertOrganizer(array $data): void
    {
        $Organizers = OrganizersTable::load();
        $Organizers->saveOrFail($Organizers->patchFromNewWithUuid($data));
    }

    public function testGetListReturnsOrganizersOrderedByName()
    {
        $this->_insertOrganizer($this->_validCreateData());

        $this->get($this->_getEndpoint());

        $json = $this->assertJsonResponseOK();
        $names = array_column($json['data'], 'name');
        $this->assertEquals([Organizer::NAME, 'NAVARRA-O'], $names);
    }

    public function testAddNewGeneratesUuidServerSide()
    {
        $this->post($this->_getEndpoint(), $this->_validCreateData());

        $json = $this->assertJsonResponseOK();
        $newId = $json['data']['id'];
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $newId);

        $saved = OrganizersTable::load()->get($newId);
        $this->assertEquals('NAVARRA-O', $saved->name);
        $this->assertEquals('ES', $saved->country_code);
        $this->assertEquals('NC', $saved->region_code);
    }

    public function testAddNewIgnoresClientProvidedExternalId()
    {
        $data = $this->_validCreateData();
        $data['external_id'] = '999';

        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), $data);

        $json = $this->assertJsonResponseOK();
        $saved = OrganizersTable::load()->get($json['data']['id']);
        $this->assertNull($saved->external_id);
    }

    public function testAddNewMissingNameReturnsValidationError()
    {
        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), ['country_code' => 'ES']);

        $this->assertException('Validation error', 400);
    }

    public function testAddNewRejectsClientProvidedNonUuidId()
    {
        $data = $this->_validCreateData();
        $data['id'] = 'navarra';

        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), $data);

        $this->assertExceptionMessage('ID must be in UUID format ISO 9834 or not provided', 400);
    }

    public function testEditUpdatesOrganizer()
    {
        $data = ['region_code' => 'MD', 'country_code' => 'ES'];

        $this->patch($this->_getEndpoint() . Organizer::ID, $data);

        $json = $this->assertJsonResponseOK();
        $this->assertEquals('MD', $json['data']['region_code']);
        $this->assertEquals('Comunidad de Madrid', $json['data']['region']);
        $this->assertEquals(Organizer::NAME, $json['data']['name']);

        $saved = OrganizersTable::load()->get(Organizer::ID);
        $this->assertEquals('MD', $saved->region_code);
        $this->assertEquals(Organizer::NAME, $saved->name);
    }

    public function testEditIgnoresIdChange()
    {
        $data = ['id' => 'hackedId', 'region_code' => 'MD'];

        $this->skipNextRequestInSwagger();
        $this->patch($this->_getEndpoint() . Organizer::ID, $data);

        $this->assertJsonResponseOK();
        $Organizers = OrganizersTable::load();
        $this->assertNull($Organizers->find()->where(['id' => 'hackedId'])->first());
        $this->assertEquals('MD', $Organizers->get(Organizer::ID)->region_code);
    }

    public function testEditNotFoundReturns404()
    {
        $this->skipNextRequestInSwagger();
        $this->patch($this->_getEndpoint() . 'doesNotExist', ['region_code' => 'MD']);

        $this->assertException('Not Found', 404);
    }

    public function testDeleteSoftDeletesOrganizer()
    {
        $this->delete($this->_getEndpoint() . Organizer::ID);
        $this->assertResponse204NoContent();

        $Organizers = OrganizersTable::load();
        $this->assertNull($Organizers->find()->where(['id' => Organizer::ID])->first());
        $deleted = $Organizers->find()->where(['id' => Organizer::ID])->withDeleted(true)->first();
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted);
    }

    public function testDeleteNotFoundReturns404()
    {
        $this->skipNextRequestInSwagger();
        $this->delete($this->_getEndpoint() . 'doesNotExist');

        $this->assertException('Not Found', 404);
    }

    public function testNonManagerIsForbidden()
    {
        $this->clearUserCache();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_NON_ADMIN_PROVIDER);

        $this->skipNextRequestInSwagger();
        $this->post($this->_getEndpoint(), $this->_validCreateData());

        $this->assertException('Forbidden', 403);
    }
}
