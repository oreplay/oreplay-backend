<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Model\Table\OauthAccessTokensTable;
use App\Model\Table\UsersTable;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;
use RestApi\Lib\Helpers\CookieHelper;

class OauthTokenControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        UsersFixture::LOAD,
        OauthClientsFixture::LOAD,
    ];

    private const REDIRECT_URL = 'https://domain.com/optional/URL/to/which/Auth0/will/redirect/the/browser/after/authorization/has/been/granted';

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
            'login_challenge' => AuthorizeControllerTest::LOGIN_CHALLENGE,
        ];

        $this->post($this->_getEndpoint(), $data);

        $return = $this->assertJsonResponseOK()['data'];

        $this->assertArrayHasKey('code', $return);
        $this->assertEquals(self::REDIRECT_URL, $return['redirect_uri']);
        $this->assertEquals('recommended_param_to_avoid_csrf', $return['state']);
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

    public function testAddNew_authorizationCodePkceFlow()
    {
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
            'code' => 'fake_test_authorization_code',
            'code_verifier' => 'test_verifier_code',
            'redirect_uri' => self::REDIRECT_URL,
            'scope' => 'offline_access'
        ];
        OauthAccessTokensTable::load()
            ->setAuthorizationCode(
                $data['code'], $data['client_id'], 50, $data['redirect_uri'],
                time() + 30, 'something offline_access');
        $mock = $this->createMock(CookieHelper::class);
        $mock->expects($this->once())->method('popLoginChallenge')
            ->willReturn([
                'challenge' => hash('sha256', $data['code_verifier'])
            ]);
        $this->mockService(CookieHelper::class, function () use ($mock) {
            return $mock;
        });

        $this->post($this->_getEndpoint(), $data);

        $return = $this->assertJsonResponseOK()['data'];
        $this->assertArrayHasKey('access_token', $return);
        $this->assertEquals('7206', $return['expires_in'], 'expires in seconds');
        $this->assertEquals('Bearer', $return['token_type']);
        $this->assertEquals(null, $return['refresh_token']);

        $db = OauthAccessTokensTable::load()->getAuthorizationCode($data['code']);
        $this->assertFalse($db);
    }

    public function testDelete_shouldLogoutWhenSendingCurrentAsEntityId()
    {
        $this->delete($this->_getEndpoint() . 'current');
        $this->assertResponseCode(204);
    }
}
