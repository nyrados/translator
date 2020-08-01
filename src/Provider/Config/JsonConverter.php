<?php
namespace Nyrados\Translator\Provider\Config;

class JsonConverter implements ConfigFileConverterInterface
{
    public function convert(string $file): array
    {
        return json_decode(file_get_contents($file), true);
    }
}