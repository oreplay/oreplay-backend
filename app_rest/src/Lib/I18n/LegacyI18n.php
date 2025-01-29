<?php

declare(strict_types = 1);

namespace App\Lib\I18n;

use App\Lib\Consts\Languages;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;
use Cake\I18n\FormatterLocator;
use Cake\I18n\I18n;
use Cake\I18n\PackageLocator;
use Cake\I18n\Translator;
use Cake\I18n\TranslatorRegistry;
use Cake\Utility\Inflector;
use L;

class LegacyI18n extends I18n
{
    public const LANGUAGES = [
        'en' => Languages::ENG,
        'de' => Languages::DEU,
        'pt' => Languages::POR,
        'ar' => Languages::ARB,
        'es' => Languages::SPA,
    ];

    public static function translators(): TranslatorRegistry
    {
        if (static::$_collection !== null) {
            return static::$_collection;
        }

        static::$_collection = new LegacyTranslatorRegistry( // start loading custom legacy classes
            new PackageLocator(),
            new FormatterLocator([
                'default' => IcuFormatter::class,
                'sprintf' => SprintfFormatter::class,
            ]),
            static::getLocale()
        );

        if (class_exists(Cache::class)) {
            static::$_collection->setCacher(Cache::pool('_cake_core_'));
        }

        return static::$_collection;
    }

    public static function getPluginLocaleDir(): string
    {
        return 'fakePlugin';
    }

    private static function getDefaultName($name): string
    {
        return $name;
    }

    public static function getTranslator(string $name = 'default', ?string $locale = null): Translator
    {
        $name = self::getDefaultName($name);
        return parent::getTranslator($name, $locale);
    }

    public static function getLocaleUrl(): string
    {
        return Configure::read('i18n.languages.' . I18n::getLocale() . '.url');
    }

    public static function getLocaleIntl(): string
    {
        return Configure::read('i18n.languages.' . I18n::getLocale() . '.intl');
    }

    public static function setLocale(string $locale): void
    {
        parent::setLocale($locale);
    }

    public static function isDefaultRtl(): string
    {
        $defaultLang = env('DEFAULT_LANGUAGE');
        return Configure::read('i18n.languages.' . $defaultLang . '.dir') == 'rtl';
    }

    public static function convertTo4Letter($lang2letter): string
    {
        $language = self::LANGUAGES[$lang2letter] ?? null;
        if (!$language) {
            throw new BadRequestException('Language (2 letter) does not exist ' . $lang2letter);
        }
        return $language;
    }

    public static function setDefaultLocale()
    {
        $defaultLang = env('DEFAULT_LANGUAGE');
        if ($defaultLang) {
            parent::setLocale(self::convertTo4Letter($defaultLang));
        } else {
            parent::setLocale(Languages::ENG);
        }
    }
}
