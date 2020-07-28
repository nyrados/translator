<?php
namespace Nyrados\Translator\Language;

class SimpleLanguage implements LanguageInterface
{
    protected $country;

    protected $region;

    public function __construct(string $country, string $region = null)
    {
        $this->country = $country;
        $this->region = $region == null ? $country : $region;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

}