<?php

namespace Nyrados\Translator\Provider\Config;

use Symfony\Component\Yaml\Yaml;

class YamlConverter implements ConfigFileConverterInterface
{
    public function convert(string $file): array
    {
        return Yaml::parseFile($file);
    }
}
