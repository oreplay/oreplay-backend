<?php

declare(strict_types = 1);

namespace App\Lib\Emails;

use App\Controller\ApiController;
use App\Lib\Consts\Languages;
use App\Lib\Consts\NotificationTypes;
use App\Lib\FullBaseUrl;
use Cake\Http\Exception\InternalErrorException;
use Firebase\JWT\JWT;
use RestApi\Lib\Exception\DetailedException;
use function Cake\I18n\__ as __;
use function Cake\I18n\__d as __d;

class VerifyEmail extends EmailBase
{
    protected function getName(): string
    {
        return __d('admin', 'Verify email address');
    }

    protected function getDescription(): ?string
    {
        return __d('admin', 'Request token to verify email address');
    }

    protected function getRecipient(): int
    {
        return self::ALL;
    }

    protected function _getI18n(): TranslatedString
    {
        return new TranslatedString('Thank you for registering', [
            Languages::ENG => '<p>Thank you for registering at O-Replay. We are delighted to have you with us.</p><p>Click the link below and login with your new account.</p>',
            Languages::SPA => '<p>Gracias por registrarte en O-Replay. Estamos encantados de tenerte con nosotros.</p><p>Haz clic en el enlace de abajo e inicia sesión con tu nueva cuenta.</p>',
        ]);
    }

    protected function _getType(): string
    {
        return NotificationTypes::VERIFY_ADDRESS;
    }

    protected function getHeader(): ?string
    {
        return __('Welcome to O-Replay');
    }

    protected function getSubject(): string
    {
        return __('Welcome to O-Replay');
    }

    protected function getNotifAction(): array
    {
        return [];
    }

    protected function getCallToActionHref(): string
    {
        return FullBaseUrl::host()  . ApiController::ROUTE_PREFIX . '/validateTokens?token=' . $this->getToken();
    }

    protected function getCallToActionLabel(): string
    {
        return __('verify');
    }

    protected function i18nValues(): array
    {
        return [];
    }

    private static function getSecretKey(): string
    {
        $secretKey = getenv('COOKIE_ENCRYPT_CONFIG');
        if (!$secretKey) {
            throw new InternalErrorException('COOKIE_ENCRYPT_CONFIG not set');
        }
        return $secretKey;
    }

    public function getToken(): string
    {
        $payload = $this->dearUser->toJsonArray();
        $payload['token_type'] = $this->_getType();
        $payload['iat'] = time();
        return JWT::encode($payload, $this->getSecretKey());
    }

    public static function decryptToken(string $token): array
    {
        $jwt = (array)JWT::decode($token, self::getSecretKey(), ['HS256']);
        $iat = $jwt['iat'] ?? null;
        if (!$iat || ($iat + 30 * 60) < time()) {
            if (($jwt['email'] ?? null) !== self::SKIP_SEND_EMAIL_ADDRESS) {
                throw new DetailedException('Token has expired');
            }
        }
        return $jwt;
    }
}
