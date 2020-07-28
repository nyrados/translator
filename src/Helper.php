<?php
namespace Nyrados\Translator;

use Nyrados\Translator\Language\LanguageInterface;

class Helper
{
    public static function languageToString(LanguageInterface $lang)
    {
        return $lang->getCountry() . '-'. $lang->getRegion();
    }
}