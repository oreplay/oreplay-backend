<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Test\Fixture\OauthAccessTokensFixture;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;

class MeControllerTest extends ApiCommonErrorsTest
{
    public $fixtures = [
        OauthClientsFixture::LOAD,
        OauthAccessTokensFixture::LOAD,
        UsersFixture::LOAD
    ];

    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/me/';
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->clearUserCache();
        $this->loadAuthToken(OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER);
    }

    public function testGetList_shouldReturn()
    {
        $this->get(ApiController::ROUTE_PREFIX . '/me');

        $expected = [
            'data' => [
                'id' => '8186ef35-e8c1-4e5c-bcc4-42bb362f050b',
                'email' => 'admin@example.',
                'first_name' => 'My Name',
                'last_name' => 'My Surname',
                'created' => '2021-01-18T10:39:23.000+00:00',
                'modified' => '2021-01-18T10:41:31.000+00:00',
            ]
        ];
        $bodyDecoded = $this->assertJsonResponseOK();
        $this->assertEquals($expected, $bodyDecoded);
        $headers = $this->_response->getHeaders();
        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'Access-Control-Allow-Origin' => ['http://dev.example.com'],
            'Access-Control-Allow-Credentials' => ['true'],
        ];
        $this->assertEquals($expectedHeaders, $headers);
    }
}
