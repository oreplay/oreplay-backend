<?php

namespace App\Test\Fixture;

use RestApi\TestSuite\Fixture\RestApiFixture;

class OauthAccessTokensFixture extends RestApiFixture
{
    const LOAD = 'app.OauthAccessTokens';
    const ACCESS_TOKEN_SELLER = '555ca191ca768883333c916a0c05bc72bdbbc89';

    public $table = 'oauth_access_tokens';
    public $records = [];

    public function __construct()
    {
        $this->records[] = [
            'access_token' => '253ca191ca768883592c916a0c05bc72bdbbc936',
            'client_id' => '54',
            'user_id' => '54',
            'expires' => (date('Y') + 1) . '-05-20 17:20:05',
            'scope' => null,
        ];
        $this->records[] = [
            'access_token' => self::ACCESS_TOKEN_SELLER,
            'client_id' => '54',
            'user_id' => UsersFixture::SELLER_ID,
            'expires' => (date('Y') + 1) . '-05-20 17:20:05',
            'scope' => null,
        ];
        parent::__construct();
    }
}
