<?php
namespace Nyrados\Translator\Language;

use InvalidArgumentException;
use Nyrados\Translator\Helper;
use Nyrados\Translator\TranslatorApi;

class Language
{
    protected $country;

    protected $region;

    public function __construct($language)
    {
        if ($language instanceof self) {
            $this->region = $language->getRegion();
            $this->country = $language->getCountry();
        } else if (is_string($language) || is_object($language) && method_exists($language, '__toSting') ) {

            $string = (string) $language;

            if(!preg_match(TranslatorApi::PARSER, $string, $output)) {
                InvalidLanguageException::throwMalformed($string);
            }

            $this->country = $output['country']; 
            $this->region  = isset($output['region']) ? $output['region'] : $output['country'];
        } else {
            throw new InvalidArgumentException ("Language must be an instance of '" . LanguageInterface::class . "' or a string");
        }
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function withRegion(string $region)
    {
        $new = clone $this;
        $new->region = $region;
        
        return $new;
    }

    public function __toString()
    {
        return $this->getId();
    }

    public function getId()
    {
        return $this->getCountry() . '-'. $this->getRegion();
    }

    public function isRegionSame(): bool
    {
        return $this->region === $this->country;
    }
}