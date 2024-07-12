<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Model\Table\UsersTable;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;

class AuthorizeControllerTest extends ApiCommonErrorsTest
{
    protected $fixtures = [
        UsersFixture::LOAD,
        OauthClientsFixture::LOAD,
    ];


    protected function _getEndpoint(): string
    {
        return ApiController::ROUTE_PREFIX . '/authorize/';
    }

    public function setUp(): void
    {
        parent::setUp();
        UsersTable::load();
    }

    public function testGetList()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
            //'login_hint' => 'prefilled_email@example.com',
            //'screen_hint' => 'login_type_screen',
            'state' => 'recommended',
            'redirect_uri' => 'Optional. The URL to which Auth0 will redirect the browser after authorization has been granted by the user.',
            'code_challenge_method' => 'S256',
            'code_challenge' => '$codeChallenge',
        ];
        $params = http_build_query($params);
        $this->get($this->_getEndpoint() . '?' . $params);

        $decoded = $this->assertJsonResponseOK();
        $this->assertEquals(['login_challenge'], array_keys($decoded['data']));
    }
}
