<?php

namespace Nyrados\Translator\Provider\Config;

use InvalidArgumentException;
use LogicException;
use Nyrados\Translator\Helper;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ArrayProvider;
use Nyrados\Translator\TranslatorApi;
use Symfony\Component\Yaml\Yaml;

class ConfigFileProvider extends ArrayProvider
{
    /** @var ConfigFileConverterInterface[] */
    private $converter = [];
    
    private $dir = '';

    public function __construct(string $dir)
    {
        Helper::createDirIfNotExists($this->dir = $dir);

        $this->setConfigFileConverter('json', new JsonConverter());

        if (class_exists(Yaml::class)) {
            $this->setConfigFileConverter('yml', new YamlConverter());
        }
    }

    public function getTranslations(Language $language, array $strings): array
    {
        $translations = parent::getTranslations($language, $strings);

        if (!empty($translations)) {
            return $translations;
        }

        foreach (scandir($this->dir) as $fileName) {
            $file = $this->dir . '/' . $fileName;
            if (is_dir($file)) {
                continue;
            }

            $split = explode('.', $fileName);
            $extension = array_pop($split);
            if (!isset($this->converter[$extension])) {
                continue;
            }


            $this->set(array_pop($split), $this->converter[$extension]->convert($file));
        }

        return parent::getTranslations($language, $strings);
    }

    public function setConfigFileConverter(string $extension, ConfigFileConverterInterface $converter)
    {
        $this->converter[$extension] = $converter;
    }
}
