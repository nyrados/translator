<?php
namespace Nyrados\Translator\Provider\Config;

use InvalidArgumentException;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ArrayProvider;
use Symfony\Component\Yaml\Yaml;

class ConfigFileProvider extends ArrayProvider
{
    /** @var ConfigFileConverterInterface[] */
    private $converter = [];
    private $dir = '';

    public function __construct(string $dir)
    {
        if(!is_dir($dir)) {
            throw new InvalidArgumentException(sprintf("Invalid Directory '%s'", $dir));
        }

        $this->dir = $dir;

        $this->setConfigFileConverter('json', new JsonConverter());
        if (class_exists(Yaml::class)) {
            $this->setConfigFileConverter('yml', new YamlConverter());
        }

    }   

    public function getTranslations(Language $language, array $strings): array
    {
        $translations = parent::getTranslations($language, $strings);
        if(!empty($translations)) {
            return $translations;
        }

        foreach ($this->converter as $extension => $converter) {
            $file = $this->dir . '/' . $language->getId() . '.' . $extension;
            if (file_exists($file)) {
                $this->set($language, $converter->convert($file));
            }
        }

        return parent::getTranslations($language, $strings);
    }

    public function setConfigFileConverter(string $extension, ConfigFileConverterInterface $converter)
    {
        $this->converter[$extension] = $converter;
    }


}