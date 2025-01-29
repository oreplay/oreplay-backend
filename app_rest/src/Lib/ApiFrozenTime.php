<?php

declare(strict_types = 1);

namespace App\Lib;

use App\Lib\Consts\Languages;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use IntlDateFormatter;
use RuntimeException;

class ApiFrozenTime extends FrozenTime
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

    protected function _formatObject($date, $format, ?string $locale): string
    {
        $pattern = '';

        if (is_array($format)) {
            [$dateFormat, $timeFormat] = $format;
        } elseif (is_int($format)) {
            $dateFormat = $timeFormat = $format;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::FULL;
            $pattern = $format;
        }

        if ($locale === null) {
            $locale = I18n::getLocale();
        }

        if (
            preg_match(
                '/@calendar=(japanese|buddhist|chinese|persian|indian|islamic|hebrew|coptic|ethiopic)/',
                $locale
            )
        ) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        } else {
            $calendar = IntlDateFormatter::GREGORIAN;
        }

        $timezone = $date->getTimezone()->getName();
        $key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";

        if (!isset(static::$_formatters[$key])) {
            if ($timezone === '+00:00' || $timezone === 'Z') {
                $timezone = 'UTC';
            } elseif ($timezone[0] === '+' || $timezone[0] === '-') {
                $timezone = 'GMT' . $timezone;
            }
            $formatter = datefmt_create(
                $locale,
                $dateFormat,
                $timeFormat,
                $timezone,
                $calendar,
                $pattern
            );
            if (empty($formatter)) {
                // NOSONAR
                throw new RuntimeException(
                    'Your version of icu does not support creating a date formatter for ' .
                    "`$key`. You should try to upgrade libicu and the intl extension."
                );
            }
            static::$_formatters[$key] = $formatter;
        }

        return static::$_formatters[$key]->format($date);
    }
}
