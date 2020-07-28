<?php
namespace Nyrados\Translator\Language;

use InvalidArgumentException;
use Nyrados\Translator\Helper;
use Nyrados\Translator\TranslatorApi;

final class LanguageDecorator extends SimpleLanguage
{
    public function __construct($language)
    {
        if ($language instanceof LanguageInterface) {
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

    public function __toString()
    {
        return $this->getId();
    }

    public function getId()
    {
        return Helper::languageToString($this);
    }

    public function isRegionSame(): bool
    {
        return $this->region === $this->country;
    }
}