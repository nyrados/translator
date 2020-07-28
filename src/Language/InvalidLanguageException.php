<?php
namespace Nyrados\Translator\Language;

class InvalidLanguageException
{

    public static function throwMalformed(string $lang)
    {
        throw new self(sprintf("Invalid Language String! Unable to parse '%s'", $lang), 1);
    }

}