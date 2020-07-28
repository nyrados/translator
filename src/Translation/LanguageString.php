<?php
namespace Nyrados\Translator\Translation;

class LanguageString
{
    private $string;

    public function __construct(string $id)
    {
        $this->string = $id;
    }
    
}