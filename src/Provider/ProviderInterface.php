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
     * the function returns null
     *
     * @param LanguageInterface $language
     * @param string $translation
     * @return Translation|null
     */
    public function getTranslation(Language $language, string $translation): ?Translation;
}