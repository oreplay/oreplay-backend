<?php

declare(strict_types = 1);

namespace App\Test\TestCase\Controller;

use App\Lib\Consts\CacheGrp;
use App\Test\Fixture\OauthAccessTokensFixture;
use Cake\Cache\Cache;

abstract class ApiCommonErrorsTest extends \RestApi\TestSuite\ApiCommonErrorsTest
{
    protected function clearUserCache()
    {
        Cache::clear(CacheGrp::EXTRALONG);
        Cache::delete('_getFirst1', CacheGrp::EXTRALONG);
    }

    public function setUp(): void
    {
        parent::setUp();
        if (!$this->currentAccessToken) {
            $this->currentAccessToken = OauthAccessTokensFixture::ACCESS_ADMIN_PROVIDER;
        }
        $_SERVER['HTTP_ORIGIN'] = 'http://dev.example.com';
        $this->loadAuthToken($this->currentAccessToken);
    }
}
