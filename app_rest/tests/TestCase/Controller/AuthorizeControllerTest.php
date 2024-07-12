<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiController;
use App\Model\Table\UsersTable;
use App\Test\Fixture\OauthClientsFixture;
use App\Test\Fixture\UsersFixture;

class AuthorizeControllerTest extends ApiCommonErrorsTest
{
    public const LOGIN_CHALLENGE = 'p9KKxVOc1/62tTZ8ROlc0A8SclFgIp0zadlqMvhI5/L4+kSS0auORU0eHzgW7SoJtUv46tYoR7gcSQ6mJETbr8U34Ivdn4iX4j9glBwWsH5nIaCtvHieBEeWpAdUg/YDxj/bYV/O/QL0X9E/rtT5JV7rP09g3izjAj9nDc5bls6V8l0nf8KMfZvhmGsM9JSjYODh7qVW10fBKHgVEslzmh9dd6TdJV9Rp6Uphcoy6R3j3VhKA0nFzqpmAoSx1js0nufSXpBtmwguSjNgUHT3k+lV5IXt7yvcgH3/LqS+Uq0=';
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

    public function testGetList_initializesTheAuthorizationCodeFlowWithPkce()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => OauthClientsFixture::DASHBOARD_CLI,
            //'login_hint' => 'prefilled_email@example.com',
            //'screen_hint' => 'login_type_screen',
            'state' => 'recommended_param_to_avoid_csrf',
            'redirect_uri' => 'https://domain.com/optional/URL/to/which/Auth0/will/redirect/the/browser/after/authorization/has/been/granted',
            'code_challenge_method' => 'S256',
            'code_challenge' => 'the_code_challenge',
        ];
        $params = http_build_query($params);
        $this->get($this->_getEndpoint() . '?' . $params);

        $decoded = $this->assertJsonResponseOK();
        $expected = ['login_challenge' => AuthorizeControllerTest::LOGIN_CHALLENGE];
        $this->assertEquals($expected, $decoded['data']);
    }
}
