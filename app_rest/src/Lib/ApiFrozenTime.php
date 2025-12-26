<?php

declare(strict_types = 1);

namespace App\Lib;

use App\Lib\Consts\Languages;
use Cake\I18n\DateTime;

class ApiFrozenTime extends DateTime
{
    public const ISO8601_WITH_MILLIS = "yyyy-MM-dd'T'HH':'mm':'ss.SSSxxx";
    public const ISO8601_NO_MILLIS = "yyyy-MM-dd'T'HH':'mm':'ssxxx";

    public static function i18nFormatMillis()
    {
        $frozenTime = new \App\Lib\ApiFrozenTime(func_get_arg(0));
        return $frozenTime->i18nFormat(
            ApiFrozenTime::ISO8601_WITH_MILLIS,
            null,
            Languages::ENG
        );
    }
}
