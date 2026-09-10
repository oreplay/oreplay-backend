<?php

declare(strict_types = 1);

namespace App\Lib;

use App\Lib\Consts\CacheGrp;
use App\Model\Entity\User;
use Cake\Cache\Cache;
use Cake\Http\Exception\BadRequestException;

class ResetPasswordCode
{
    public const string CACHE_KEY = '_codeResetPassword_';
    private const string ATTEMPTS_CACHE_KEY = '_attemptsResetPassword_';
    private const string EMAILS_SENT_CACHE_KEY = '_emailsResetPassword_';
    private const int MAX_ATTEMPTS = 5;
    public const int MAX_EMAILS_PER_WINDOW = 10;
    private const int EMAILS_WINDOW_SECONDS = 3 * 3600;
    private const int CODE_LENGTH = 6;

    public static function canGenerateFor(User $user): bool
    {
        return self::_readEmailsWindow($user)['sent'] < self::MAX_EMAILS_PER_WINDOW;
    }

    public static function generateFor(User $user): string
    {
        self::_registerEmailSent($user);
        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        Cache::write(self::CACHE_KEY . $user->id, $code, CacheGrp::SHORT);
        Cache::delete(self::ATTEMPTS_CACHE_KEY . $user->id, CacheGrp::SHORT);
        return $code;
    }

    public static function assertValidFor(User $user, string $code): void
    {
        $storedCode = Cache::read(self::CACHE_KEY . $user->id, CacheGrp::SHORT);
        if (!$storedCode) {
            throw new BadRequestException('Code not found');
        }
        if (!hash_equals($storedCode, $code)) {
            self::_registerFailedAttempt($user);
            throw new BadRequestException('Invalid code');
        }
    }

    public static function invalidateFor(User $user): void
    {
        Cache::delete(self::CACHE_KEY . $user->id, CacheGrp::SHORT);
        Cache::delete(self::ATTEMPTS_CACHE_KEY . $user->id, CacheGrp::SHORT);
    }

    private static function _readEmailsWindow(User $user): array
    {
        $window = Cache::read(self::EMAILS_SENT_CACHE_KEY . $user->id, CacheGrp::EXTRALONG);
        if (!$window || $window['closes_at'] <= time()) {
            return ['sent' => 0, 'closes_at' => time() + self::EMAILS_WINDOW_SECONDS];
        }
        return $window;
    }

    private static function _registerEmailSent(User $user): void
    {
        $window = self::_readEmailsWindow($user);
        $window['sent']++;
        Cache::write(self::EMAILS_SENT_CACHE_KEY . $user->id, $window, CacheGrp::EXTRALONG);
    }

    private static function _registerFailedAttempt(User $user): void
    {
        $failedAttempts = (int) Cache::read(self::ATTEMPTS_CACHE_KEY . $user->id, CacheGrp::SHORT) + 1;
        Cache::write(self::ATTEMPTS_CACHE_KEY . $user->id, $failedAttempts, CacheGrp::SHORT);
        if ($failedAttempts >= self::MAX_ATTEMPTS) {
            self::invalidateFor($user);
        }
    }
}
