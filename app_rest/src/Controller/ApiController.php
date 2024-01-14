<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Component\OAuthServerComponent;
use App\Lib\I18n\LegacyI18n;
use App\Lib\Oauth\OAuthServer;
use RestApi\Controller\Component\ApiRestCorsComponent;
use RestApi\Controller\RestApiController;

/**
 * @property OAuthServerComponent $OAuthServer
 */
abstract class ApiController extends RestApiController
{
    const ROUTE_PREFIX = '/api/v1';

    private $_localOauth = null;

    protected function _setUserLang(): void
    {
    }

    protected function _loadOAuthServerComponent(): OAuthServerComponent
    {
        $this->loadComponentFromClass(OAuthServerComponent::class);
        return $this->OAuthServer;
    }

    protected function _loadCorsComponent(): ApiRestCorsComponent
    {
        ApiRestCorsComponent::load($this);
        return $this->ApiCors;
    }

    protected function _setLanguage(): void
    {
        LegacyI18n::setDefaultLocale();
    }

    protected function getLocalOauth(): OAuthServer
    {
        if ($this->_localOauth) {
            return $this->_localOauth;
        }
        $this->_localOauth = new OAuthServer();
        $this->_localOauth->setupOauth($this);
        return $this->_localOauth;
    }
}
