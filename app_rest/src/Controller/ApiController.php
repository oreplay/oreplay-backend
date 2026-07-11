<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Component\OAuthServerComponent;
use App\Lib\I18n\LegacyI18n;
use App\Lib\Oauth\OAuthServer;
use Cake\Http\Exception\InternalErrorException;
use RestApi\Controller\Component\ApiRestCorsComponent;
use RestApi\Controller\RestApiController;

/**
 * @property OAuthServerComponent $OAuthServer
 */
abstract class ApiController extends RestApiController
{
    const ROUTE_PREFIX = '/api/v1';

    private $_localOauth = null;
    private ApiRestCorsComponent $ApiCors;
    public $OAuthServer;

    protected function _setUserLang(): void
    {
    }

    protected function _loadOAuthServerComponent(): OAuthServerComponent
    {
        /** @var OAuthServerComponent $oauthComponent */
        $oauthComponent = $this->loadComponentFromClass(OAuthServerComponent::class);
        $this->OAuthServer = $oauthComponent;
        return $this->OAuthServer;
    }

    protected function _loadCorsComponent(): ApiRestCorsComponent
    {
        $this->ApiCors = ApiRestCorsComponent::load($this);
        return $this->ApiCors;
    }

    protected function _setLanguage(): void
    {
        LegacyI18n::setDefaultLocale();
    }

    /**
     * @deprecated use getManualOauth() instead, only use getLocalOauth for overwriting
     */
    protected function getLocalOauth(): OAuthServer
    {
        if ($this->_localOauth) {
            return $this->_localOauth;
        }
        $this->_localOauth = new OAuthServer();
        $this->_localOauth->setupOauth($this);
        return $this->_localOauth;
    }

    protected function getManualOauth(): OAuthServer
    {
        if (!$this->isPublicController()) {
            throw new InternalErrorException(
                'getManualOauth() is only for public controllers, where the request lifecycle does '
                . 'not verify auth. This controller is non-public: use $this->OAuthServer, which '
                . 'OAuthServerComponent has already verified for the request.'
            );
        }
        return $this->getLocalOauth();
    }
}
