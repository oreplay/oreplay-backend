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
    private const int MAX_ATTEMPTS = 5;
    private const int CODE_LENGTH = 6;

    public static function generateFor(User $user): string
    {
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

    private static function _registerFailedAttempt(User $user): void
    {
        $failedAttempts = (int) Cache::read(self::ATTEMPTS_CACHE_KEY . $user->id, CacheGrp::SHORT) + 1;
        Cache::write(self::ATTEMPTS_CACHE_KEY . $user->id, $failedAttempts, CacheGrp::SHORT);
        if ($failedAttempts >= self::MAX_ATTEMPTS) {
            self::invalidateFor($user);
        }
    }
}
