<?php

namespace Nyrados\Translator\Provider\Config;

use Nyrados\Translator\Translation\UndefinedStringCollector;

interface ConfigFileConverterInterface
{
    /**
     * Converts the content of the given file to an array of translations
     *
     * The array must be in the following format:
     * $string => $name
     *
     * @param string $file
     * @return array
     */
    public function convert(string $file): array;
    
    public function saveMissing(string $file, UndefinedStringCollector $strings): void;
}
