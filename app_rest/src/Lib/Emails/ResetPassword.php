<?php

declare(strict_types = 1);

namespace App\Lib\Emails;

use App\Lib\Consts\CacheGrp;
use App\Lib\Consts\Languages;
use App\Lib\Consts\NotificationTypes;
use Cake\Cache\Cache;
use function Cake\I18n\__ as __;
use function Cake\I18n\__d as __d;

class ResetPassword extends EmailBase
{
    public const string CACHE_KEY = '_codeResetPassword_';

    private string $_code = '';

    protected function getName(): string
    {
        return __d('admin', 'Reset password');
    }

    protected function getDescription(): ?string
    {
        return __d('admin', 'Request token to reset the password');
    }

    protected function getRecipient(): int
    {
        return self::ALL;
    }

    protected function _getI18n(): TranslatedString
    {
        return new TranslatedString('Reset password', [
            Languages::ENG => '<p>Reset password requested.</p><p>This is the code to change your password: {2}. Valid for 5 minutes.</p>',
            Languages::SPA => '<p>Petición de cambio de contraseña.</p><p>Este es el código para cambiar tu contraseña: {2}. Válido durante 5 minutos.</p>',
        ]);
    }

    protected function _getType(): string
    {
        return NotificationTypes::RESET_PASSWORD;
    }

    protected function getHeader(): ?string
    {
        return __('Reset password');
    }

    protected function getSubject(): string
    {
        return __('Reset password');
    }

    protected function getNotifAction(): array
    {
        return [];
    }

    protected function i18nValues(): array
    {
        return [
            $this->getCode()
        ];
    }

    protected function getCallToActionHref(): string
    {
        return '';
    }

    protected function getCallToActionLabel(): string
    {
        return '';
    }

    public function getCode(): string
    {
        if (!$this->_code) {
            $this->_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }
        Cache::write(ResetPassword::CACHE_KEY . $this->dearUser->id, $this->_code, CacheGrp::SHORT);
        return $this->_code;
    }

    public static function getCodeForUser(string $uid): string|false
    {
        return Cache::read(ResetPassword::CACHE_KEY . $uid, CacheGrp::SHORT);
    }
}
