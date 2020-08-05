<?php
namespace Nyrados\Translator\Provider\Config;

use Nyrados\Translator\Translation\UndefinedStringCollector;

class JsonConverter implements ConfigFileConverterInterface
{
    public function convert(string $file): array
    {
        return json_decode(file_get_contents($file), true);
    }

    public function saveMissing(string $file, UndefinedStringCollector $strings): void
    {
        $data = [];
        if (file_exists($file)) {
            $data = $this->convert($file);
        }

        $i = 0;
        foreach ($strings as $string => $context) {

            $comment =  ConfigFileProvider::generateContextComment($context);
            $hash = substr(md5($comment . '.' . $string), 0, 5);

            if (!empty($context)) {
                $data['//' . $hash . ': ' . $comment] = null;
            }
            $data[$string] = null;

        }

        $file = fopen($file, 'w+');
        fwrite($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fclose($file);
    }
}