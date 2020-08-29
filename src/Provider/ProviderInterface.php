<?php

namespace Nyrados\Translator\Provider;

use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Language\LanguageInterface;
use Nyrados\Translator\Translation\Translation;

/**
 * Describes a class that can provide Translations for a specific Language
 */
interface ProviderInterface
{
    /**
     * Returns a translation if provider has a translation.
     *
     * If doesn't provider have a translation for the string
     * the function returns an empty array
     *
     * @param LanguageInterface $language
     * @param string[] $string
     * @return Translation[]
     */
    public function getTranslations(Language $language, array $strings): array;
}
