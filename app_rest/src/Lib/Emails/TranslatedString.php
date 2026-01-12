<?php

declare(strict_types = 1);

namespace App\Lib\Emails;

use App\Lib\I18n\LegacyI18n;

class TranslatedString
{
    private string $key;
    private array $translations;

    public function __construct(string $key, array $translations = [])
    {
        $this->key = $key;
        $this->translations = $translations;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTranslation(string $lang = ''): string
    {
        if (!$lang) {
            $lang = LegacyI18n::getLocale();
        }
        if (isset($this->translations[$lang])) {
            return $this->translations[$lang];
        }
        $oldLocale = LegacyI18n::getLocale();
        LegacyI18n::setLocale($lang);
        $translator = LegacyI18n::getTranslator();
        $toRet = $translator->translate($this->key);
        LegacyI18n::setLocale($oldLocale);
        return $toRet;
    }

    public function overwriteLang(string $lang, string $customTranslation): void
    {
        $this->translations[$lang] = $customTranslation;
    }
}
