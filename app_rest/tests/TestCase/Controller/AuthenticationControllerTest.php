<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;

class AuthenticationControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        UsersFixture::LOAD,
        OauthClientsFixture::LOAD,
    ];


    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/authentication/';
    }

    public function setUp(): void
    {
        parent::setUp();
        UsersTable::load();
    }

    public function testAddNew_login()
    {
        $data = [
            'username' => UsersFixture::USER_ADMIN_EMAIL,
            'password' => 'passpass',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
            'grant_type' => 'password',
        ];

        $this->post($this->_getEndpoint(), $data);

        $this->assertJsonResponseOK();
        $return = json_decode($this->_getBodyAsString(), true)['data'];

        $this->assertArrayHasKey('access_token', $return);
        $this->assertEquals('7206', $return['expires_in'], 'expires in seconds');
        $this->assertEquals('Bearer', $return['token_type']);
    }

    public function testAddNew_loginShouldRememberMe()
    {
        $data = [
            'username' => UsersFixture::USER_ADMIN_EMAIL,
            'password' => 'passpass',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
            'grant_type' => 'password',
            'remember_me' => true,
        ];

        $this->post($this->_getEndpoint(), $data);

        $this->assertJsonResponseOK();
        $return = json_decode($this->_getBodyAsString(), true)['data'];

        $this->assertArrayHasKey('access_token', $return);
        $this->assertEquals('172806', $return['expires_in'], 'expires in seconds');
        $this->assertEquals('Bearer', $return['token_type']);
    }

    public function testAddNew_loginShouldExceptionWithInvalidPayload()
    {
        $data = [
            'username' => UsersFixture::USER_ADMIN_EMAIL,
            'password' => 'passpass',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
        ];

        $this->post($this->_getEndpoint(), $data);

        $this->assertResponseError();
        $return = json_decode($this->_getBodyAsString(), true);

        $this->assertEquals('Invalid grant_type', $return['message']);
    }

    public function testDelete_shouldLogoutWhenSendingCurrentAsEntityId()
    {
        $this->delete($this->_getEndpoint() . 'current');
        $this->assertResponseCode(204);
    }
}
