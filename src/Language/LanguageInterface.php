<?php
namespace Nyrados\Translator\Language;

interface LanguageInterface
{
    /**
     * Returns Countrycode
     * 
     * This SHOULD be in the alpha-2 code format of ISO 3166-1 
     *
     * @return string
     */
    public function getCountry(): string;

    /**
     * Returns code to specify the region.
     * 
     * This MAY be the same or another Country Code
     *
     * @return string
     */
    public function getRegion(): string;
}